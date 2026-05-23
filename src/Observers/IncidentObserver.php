<?php

namespace TrackAnyDevice\Core\Observers;

use TrackAnyDevice\Core\Enums\IncidentStatus;
use TrackAnyDevice\Core\Enums\WorkflowTriggerType;
use TrackAnyDevice\Core\Models\Incident;
use TrackAnyDevice\Core\Models\User;
use TrackAnyDevice\Core\Notifications\IncidentNotification;
use TrackAnyDevice\Core\Workflows\WorkflowDispatcher;

class IncidentObserver
{
    public function __construct(private readonly WorkflowDispatcher $dispatcher) {}

    public function created(Incident $incident): void
    {
        User::whereIn('role', ['admin', 'supervisor'])->each(
            fn (User $user) => $user->notify(new IncidentNotification($incident))
        );

        $this->dispatcher->dispatchForIncident($incident, WorkflowTriggerType::IncidentCreated);
    }

    public function updated(Incident $incident): void
    {
        // Fire incident.closed workflows when an incident transitions
        // into a terminal status (resolved / dismissed). The status enum
        // exposes isTerminal() to keep the rule centralized.
        if (! $incident->wasChanged('status')) {
            return;
        }

        $newStatus = $incident->status;

        // Eloquent's getRawOriginal bypasses enum casting and returns the
        // raw column value (string), so we can safely tryFrom() it.
        $rawOld = $incident->getRawOriginal('status');
        $oldStatus = is_string($rawOld) ? IncidentStatus::tryFrom($rawOld) : null;

        $becameTerminal = $newStatus->isTerminal() && ! ($oldStatus?->isTerminal() ?? false);
        if (! $becameTerminal) {
            return;
        }

        $this->dispatcher->dispatchForIncident($incident, WorkflowTriggerType::IncidentClosed);
    }
}
