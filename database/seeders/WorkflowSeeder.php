<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Enums\WorkflowTriggerType;
use TrackAnyDevice\Core\Models\Tenant;
use TrackAnyDevice\Core\Models\TenantStatus;
use TrackAnyDevice\Core\Models\Workflow;
use Illuminate\Database\Seeder;

/**
 * Seeds one example workflow per trigger type for the first approved tenant.
 *
 * Idempotent — uses updateOrCreate keyed on (tenant_id, name) so re-running
 * the seeder updates existing rows in place rather than spawning duplicates.
 */
class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::on(config('tenancy.database.central_connection'))
            ->where('status', TenantStatus::Approved)
            ->orderBy('id')
            ->first();

        if (! $tenant) {
            return;
        }

        $this->incidentCreatedSample($tenant);
        $this->incidentClosedSample($tenant);
        $this->scheduledSample($tenant);
    }

    private function incidentCreatedSample(Tenant $tenant): void
    {
        Workflow::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'SOS — page supervisor'],
            [
                'user_id' => null,
                'description' => 'Notifies the beat supervisor immediately on SOS, escalates after 60s if still open.',
                'trigger_type' => WorkflowTriggerType::IncidentCreated,
                'trigger_config' => ['event_type' => 'sos'],
                'graph' => [
                    'nodes' => [
                        $this->triggerNode(),
                        $this->actionNode('notify-1', 'notify', 'Notify supervisor', 300, 100, [
                            'channels' => ['in_app', 'sms'],
                            'message' => 'SOS triggered by {device.name} at {incident.triggered_at}',
                        ]),
                        $this->actionNode('wait-1', 'wait', 'Wait 60s', 500, 100, ['seconds' => 60]),
                        $this->actionNode('escalate-1', 'escalate_incident', 'Escalate', 700, 100, [
                            'priority' => 'critical',
                        ]),
                    ],
                    'edges' => [
                        ['id' => 'e1', 'source' => 'trigger', 'target' => 'notify-1'],
                        ['id' => 'e2', 'source' => 'notify-1', 'target' => 'wait-1'],
                        ['id' => 'e3', 'source' => 'wait-1', 'target' => 'escalate-1'],
                    ],
                ],
                'is_enabled' => false,
                'version' => 1,
            ],
        );
    }

    private function incidentClosedSample(Tenant $tenant): void
    {
        Workflow::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Incident closed — webhook downstream system'],
            [
                'user_id' => null,
                'description' => 'POSTs the resolution to an external ticketing system when any incident is resolved.',
                'trigger_type' => WorkflowTriggerType::IncidentClosed,
                'trigger_config' => [],
                'graph' => [
                    'nodes' => [
                        $this->triggerNode(),
                        $this->actionNode('webhook-1', 'webhook', 'Webhook ticketing', 300, 100, [
                            'url' => 'https://example.com/incidents/resolved',
                            'method' => 'POST',
                        ]),
                    ],
                    'edges' => [
                        ['id' => 'e1', 'source' => 'trigger', 'target' => 'webhook-1'],
                    ],
                ],
                'is_enabled' => false,
                'version' => 1,
            ],
        );
    }

    private function scheduledSample(Tenant $tenant): void
    {
        Workflow::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Hourly device-health check (sample)'],
            [
                'user_id' => null,
                'description' => 'Sample time-triggered workflow — runs hourly and notifies supervisors of offline devices.',
                'trigger_type' => WorkflowTriggerType::Time,
                'trigger_config' => ['cron' => '0 * * * *', 'timezone' => 'UTC'],
                'graph' => [
                    'nodes' => [
                        $this->triggerNode(),
                        $this->actionNode('notify-1', 'notify', 'Notify supervisors', 300, 100, [
                            'channels' => ['in_app'],
                            'message' => 'Hourly device-health check fired at {trigger.fired_at}',
                        ]),
                    ],
                    'edges' => [
                        ['id' => 'e1', 'source' => 'trigger', 'target' => 'notify-1'],
                    ],
                ],
                'is_enabled' => false,
                'version' => 1,
            ],
        );
    }

    private function triggerNode(): array
    {
        return [
            'id' => 'trigger',
            'type' => 'workflowNode',
            'position' => ['x' => 100, 'y' => 100],
            'data' => ['action_type' => 'trigger', 'label' => 'Trigger', 'config' => []],
        ];
    }

    private function actionNode(string $id, string $type, string $label, int $x, int $y, array $config): array
    {
        return [
            'id' => $id,
            'type' => 'workflowNode',
            'position' => ['x' => $x, 'y' => $y],
            'data' => ['action_type' => $type, 'label' => $label, 'config' => $config],
        ];
    }
}
