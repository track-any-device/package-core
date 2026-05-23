<?php

namespace TrackAnyDevice\Core\Database\Factories;

use TrackAnyDevice\Core\Enums\AssigneeStatus;
use TrackAnyDevice\Core\Models\Assignee;
use TrackAnyDevice\Core\Models\AssigneeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignee>
 */
class AssigneeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'assignee_type_id' => AssigneeType::factory(),
            'name' => $this->faker->name(),
            'code' => $this->faker->unique()->bothify('ASN-#####'),
            'status' => AssigneeStatus::Active,
            'metadata' => null,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => AssigneeStatus::Inactive]);
    }

    public function onLeave(): static
    {
        return $this->state(['status' => AssigneeStatus::OnLeave]);
    }

    public function terminated(): static
    {
        return $this->state(['status' => AssigneeStatus::Terminated]);
    }
}
