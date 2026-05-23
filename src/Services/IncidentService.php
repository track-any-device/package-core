<?php

namespace TrackAnyDevice\Core\Services;

use TrackAnyDevice\Core\Enums\AlertRuleEventType;
use TrackAnyDevice\Core\Enums\IncidentStatus;
use TrackAnyDevice\Core\Exceptions\AssignmentException;
use TrackAnyDevice\Core\Models\Incident;
use TrackAnyDevice\Core\Models\User;

class IncidentService
{
    /**
     * Acknowledge an open or escalated incident.
     */
    public function acknowledge(Incident $incident, User $by): Incident
    {
        $this->assertTransition($incident, IncidentStatus::Acknowledged);

        $incident->update([
            'status' => IncidentStatus::Acknowledged,
            'acknowledged_by' => $by->id,
            'acknowledged_at' => now(),
        ]);

        return $incident->fresh();
    }

    /**
     * Resolve an incident with optional resolution notes.
     */
    public function resolve(Incident $incident, User $by, ?string $notes = null): Incident
    {
        $this->assertTransition($incident, IncidentStatus::Resolved);

        $incident->update([
            'status' => IncidentStatus::Resolved,
            'resolved_by' => $by->id,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);

        return $incident->fresh();
    }

    /**
     * Dismiss an incident. SOS incidents cannot be dismissed.
     */
    public function dismiss(Incident $incident, User $by, ?string $notes = null): Incident
    {
        if ($incident->event_type === AlertRuleEventType::Sos) {
            throw new AssignmentException('SOS incidents cannot be dismissed — they must be resolved.');
        }

        $this->assertTransition($incident, IncidentStatus::Dismissed);

        $incident->update([
            'status' => IncidentStatus::Dismissed,
            'resolved_by' => $by->id,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);

        return $incident->fresh();
    }

    /**
     * Escalate an open or acknowledged incident.
     */
    public function escalate(Incident $incident, User $by): Incident
    {
        $this->assertTransition($incident, IncidentStatus::Escalated);

        $incident->update([
            'status' => IncidentStatus::Escalated,
            'acknowledged_by' => $by->id,
            'acknowledged_at' => $incident->acknowledged_at ?? now(),
        ]);

        return $incident->fresh();
    }

    private function assertTransition(Incident $incident, IncidentStatus $to): void
    {
        if (! $incident->status->canTransitionTo($to)) {
            throw new AssignmentException(
                "Cannot transition incident from [{$incident->status->label()}] to [{$to->label()}]."
            );
        }
    }
}
