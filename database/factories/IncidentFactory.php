<?php

namespace TrackAnyDevice\Core\Database\Factories;

use TrackAnyDevice\Core\Enums\AlertRuleEventType;
use TrackAnyDevice\Core\Enums\IncidentPriority;
use TrackAnyDevice\Core\Enums\IncidentStatus;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Models\Incident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'device_id' => Device::factory(),
            'assignee_id' => null,
            'beat_id' => null,
            'alert_rule_id' => null,
            'event_type' => fake()->randomElement(AlertRuleEventType::cases()),
            'priority' => fake()->randomElement(IncidentPriority::cases()),
            'status' => IncidentStatus::Open,
            'latitude' => fake()->latitude(30, 33),
            'longitude' => fake()->longitude(71, 75),
            'triggered_at' => now(),
            'acknowledged_by' => null,
            'acknowledged_at' => null,
            'resolved_by' => null,
            'resolved_at' => null,
            'resolution_notes' => null,
            'payload' => null,
        ];
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => IncidentPriority::Critical,
        ]);
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IncidentStatus::Open,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IncidentStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }

    public function sos(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AlertRuleEventType::Sos,
            'priority' => IncidentPriority::Critical,
        ]);
    }
}
