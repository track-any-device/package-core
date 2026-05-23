<?php

namespace TrackAnyDevice\Core\Database\Factories;

use TrackAnyDevice\Core\Enums\BeatAssignmentStatus;
use TrackAnyDevice\Core\Models\Beat;
use TrackAnyDevice\Core\Models\BeatAssignment;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeatAssignment>
 */
class BeatAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'beat_id' => Beat::factory(),
            'assigned_by' => User::factory(),
            'effective_from' => now(),
            'effective_to' => null,
            'status' => BeatAssignmentStatus::Active,
            'reason' => null,
            'notes' => null,
        ];
    }

    public function ended(): static
    {
        return $this->state([
            'effective_to' => now(),
            'status' => BeatAssignmentStatus::Ended,
        ]);
    }
}
