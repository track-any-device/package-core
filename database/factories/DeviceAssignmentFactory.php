<?php

namespace TrackAnyDevice\Core\Database\Factories;

use TrackAnyDevice\Core\Enums\DeviceAssignmentStatus;
use TrackAnyDevice\Core\Models\Assignee;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Models\DeviceAssignment;
use TrackAnyDevice\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceAssignment>
 */
class DeviceAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'assignee_id' => Assignee::factory(),
            'assigned_by' => User::factory(),
            'assigned_at' => now(),
            'returned_at' => null,
            'condition_out' => $this->faker->randomElement(['good', 'fair', 'poor']),
            'condition_in' => null,
            'status' => DeviceAssignmentStatus::Active,
            'notes' => null,
        ];
    }

    public function returned(): static
    {
        return $this->state([
            'returned_at' => now(),
            'condition_in' => $this->faker->randomElement(['good', 'fair', 'poor']),
            'status' => DeviceAssignmentStatus::Returned,
        ]);
    }
}
