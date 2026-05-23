<?php

namespace TrackAnyDevice\Core\Workflows\Actions;

use TrackAnyDevice\Core\Enums\IncidentPriority;
use TrackAnyDevice\Core\Enums\IncidentStatus;
use TrackAnyDevice\Core\Models\Incident;
use TrackAnyDevice\Core\Scopes\TenantScope;
use TrackAnyDevice\Core\Workflows\Contracts\WorkflowAction;
use Illuminate\Support\Facades\Log;

/**
 * Promote an incident's priority and (optionally) reopen it.
 *
 * Targets the incident referenced in $context['incident']['id']. If
 * the incident has already been resolved/dismissed, this step reopens
 * it and increments reopen_count so the audit trail reflects the
 * workflow-driven re-escalation.
 */
class EscalateIncidentAction implements WorkflowAction
{
    public function execute(array $config, array $context): array
    {
        $incidentId = $context['incident']['id'] ?? null;
        if (! $incidentId) {
            return ['status' => 'failed', 'error' => 'No incident in context'];
        }

        $incident = Incident::withoutGlobalScope(TenantScope::class)->find($incidentId);
        if (! $incident) {
            return ['status' => 'failed', 'error' => "Incident {$incidentId} not found"];
        }

        $newPriority = $this->parsePriority($config['priority'] ?? 'critical');
        if (! $newPriority) {
            return ['status' => 'failed', 'error' => 'Invalid priority'];
        }

        try {
            $previousPriority = $incident->priority;
            $incident->priority = $newPriority;

            if ($incident->isTerminal()) {
                $history = $incident->reopen_history ?? [];
                $history[] = [
                    'reopened_at' => now()->toIso8601String(),
                    'reason' => 'workflow_escalation',
                ];
                $incident->reopen_history = $history;
                $incident->reopen_count = ($incident->reopen_count ?? 0) + 1;
                $incident->status = IncidentStatus::Open;
                $incident->resolved_at = null;
                $incident->resolved_by = null;
            }

            $incident->save();
        } catch (\Throwable $e) {
            Log::error('Workflow escalate_incident failed', [
                'incident_id' => $incidentId,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'failed', 'error' => $e->getMessage()];
        }

        return [
            'status' => 'completed',
            'output' => [
                'incident_id' => $incident->id,
                'previous_priority' => $previousPriority->value,
                'new_priority' => $newPriority->value,
                'reopened' => $incident->reopen_count > 0,
            ],
        ];
    }

    private function parsePriority(string $value): ?IncidentPriority
    {
        return IncidentPriority::tryFrom($value);
    }
}
