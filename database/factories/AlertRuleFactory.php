<?php

namespace TrackAnyDevice\Core\Database\Factories;

use TrackAnyDevice\Core\Enums\AlertRuleEventType;
use TrackAnyDevice\Core\Enums\IncidentPriority;
use TrackAnyDevice\Core\Models\AlertRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlertRule>
 */
class AlertRuleFactory extends Factory
{
    public function definition(): array
    {
        $eventType = fake()->randomElement(AlertRuleEventType::cases());

        return [
            'name' => $eventType->label().' Rule',
            'description' => fake()->optional()->sentence(),
            'event_type' => $eventType,
            'device_type_slug' => null,
            'scope' => 'all',
            'priority' => fake()->randomElement(IncidentPriority::cases()),
            'is_enabled' => true,
            'condition' => null,
            'notification_channels' => ['in_app'],
            'escalation_rules' => null,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }

    public function forEvent(AlertRuleEventType $eventType): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => $eventType,
            'name' => $eventType->label().' Rule',
        ]);
    }
}
