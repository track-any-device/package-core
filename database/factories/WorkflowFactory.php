<?php

namespace TrackAnyDevice\Core\Database\Factories;

use TrackAnyDevice\Core\Enums\WorkflowTriggerType;
use TrackAnyDevice\Core\Models\Tenant;
use TrackAnyDevice\Core\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workflow>
 */
class WorkflowFactory extends Factory
{
    protected $model = Workflow::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => null,
            'created_by' => null,
            'name' => $this->faker->words(3, true),
            'description' => null,
            'trigger_type' => WorkflowTriggerType::IncidentCreated,
            'trigger_config' => [],
            'graph' => [
                'nodes' => [
                    [
                        'id' => 'trigger',
                        'type' => 'workflowNode',
                        'position' => ['x' => 100, 'y' => 100],
                        'data' => ['action_type' => 'trigger', 'label' => 'Trigger', 'config' => []],
                    ],
                ],
                'edges' => [],
            ],
            'is_enabled' => true,
            'version' => 1,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['is_enabled' => false]);
    }

    public function withNotifyStep(string $message = 'Test notification'): static
    {
        return $this->state(fn () => [
            'graph' => [
                'nodes' => [
                    [
                        'id' => 'trigger',
                        'type' => 'workflowNode',
                        'position' => ['x' => 100, 'y' => 100],
                        'data' => ['action_type' => 'trigger', 'label' => 'Trigger', 'config' => []],
                    ],
                    [
                        'id' => 'notify-1',
                        'type' => 'workflowNode',
                        'position' => ['x' => 300, 'y' => 100],
                        'data' => [
                            'action_type' => 'notify',
                            'label' => 'Notify',
                            'config' => ['channels' => ['in_app'], 'message' => $message],
                        ],
                    ],
                ],
                'edges' => [
                    ['id' => 'e1', 'source' => 'trigger', 'target' => 'notify-1'],
                ],
            ],
        ]);
    }

    public function withWebhookStep(string $url): static
    {
        return $this->state(fn () => [
            'graph' => [
                'nodes' => [
                    [
                        'id' => 'trigger',
                        'type' => 'workflowNode',
                        'position' => ['x' => 100, 'y' => 100],
                        'data' => ['action_type' => 'trigger', 'label' => 'Trigger', 'config' => []],
                    ],
                    [
                        'id' => 'webhook-1',
                        'type' => 'workflowNode',
                        'position' => ['x' => 300, 'y' => 100],
                        'data' => [
                            'action_type' => 'webhook',
                            'label' => 'Webhook',
                            'config' => ['url' => $url, 'method' => 'POST'],
                        ],
                    ],
                ],
                'edges' => [
                    ['id' => 'e1', 'source' => 'trigger', 'target' => 'webhook-1'],
                ],
            ],
        ]);
    }
}
