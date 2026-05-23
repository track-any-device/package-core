<?php

namespace TrackAnyDevice\Core\Workflows;

use TrackAnyDevice\Core\Enums\WorkflowTriggerType;
use TrackAnyDevice\Core\Jobs\Workflows\RunWorkflowJob;
use TrackAnyDevice\Core\Models\Incident;
use TrackAnyDevice\Core\Models\Workflow;

/**
 * Resolves which workflows should fire for an event and queues them.
 *
 * Decoupled from the executor so caller code (observers, scheduler) only
 * needs to call dispatch() with a trigger type + payload — the dispatcher
 * picks the matching workflows and pushes each onto the queue.
 */
class WorkflowDispatcher
{
    public function dispatchForIncident(Incident $incident, WorkflowTriggerType $trigger): int
    {
        $workflows = Workflow::query()
            ->where('tenant_id', $incident->tenant_id)
            ->where('trigger_type', $trigger->value)
            ->where('is_enabled', true)
            ->get();

        if ($workflows->isEmpty()) {
            return 0;
        }

        $context = $this->buildIncidentContext($incident);

        foreach ($workflows as $workflow) {
            RunWorkflowJob::dispatch($workflow->id, $trigger->value, $context);
        }

        return $workflows->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildIncidentContext(Incident $incident): array
    {
        $incident->loadMissing([
            'device:id,name,imei,user_id,tenant_id',
            'beat:id,name,supervisor_id',
            // The supervisor is now an Assignee, not a User — load it
            // explicitly so notify steps can reach the field phone.
            'beat.supervisor:id,name,code,metadata',
            'tenant:id,name,slug',
        ]);

        $beatSupervisor = $incident->beat?->supervisor;

        return [
            'incident' => [
                'id' => $incident->id,
                'event_type' => $incident->event_type->value,
                'priority' => $incident->priority->value,
                'status' => $incident->status->value,
                'triggered_at' => $incident->triggered_at?->toIso8601String(),
                'latitude' => $incident->latitude !== null ? (float) $incident->latitude : null,
                'longitude' => $incident->longitude !== null ? (float) $incident->longitude : null,
                'assignee_id' => $incident->assignee_id,
            ],
            'device' => $incident->device ? [
                'id' => $incident->device->id,
                'name' => $incident->device->name,
                'imei' => $incident->device->imei,
                'user_id' => $incident->device->user_id,
            ] : null,
            'beat' => $incident->beat ? [
                'id' => $incident->beat->id,
                'name' => $incident->beat->name,
                // `supervisor` is an Assignee — expose name + phone for SMS
                // notification templates. NotifyUsersAction routes user-
                // facing channels through device.user_id / assignee_id.
                'supervisor' => $beatSupervisor ? [
                    'id' => $beatSupervisor->id,
                    'name' => $beatSupervisor->name,
                    'code' => $beatSupervisor->code,
                    'phone' => $beatSupervisor->metadata['phone'] ?? null,
                ] : null,
            ] : null,
            'tenant' => $incident->tenant ? [
                'id' => $incident->tenant->id,
                'name' => $incident->tenant->name,
                'slug' => $incident->tenant->slug,
            ] : null,
        ];
    }
}
