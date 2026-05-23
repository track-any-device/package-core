<?php

namespace TrackAnyDevice\Core\Database\Factories;

use TrackAnyDevice\Core\Enums\DeviceCommandStatus;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Models\DeviceCommand;
use TrackAnyDevice\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceCommand>
 */
class DeviceCommandFactory extends Factory
{
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'command_type' => fake()->randomElement(['check', 'query', 'vol', 'md', 'adminip']),
            'command_payload' => fake()->word().'123456',
            'channel' => 'sms',
            'status' => DeviceCommandStatus::Pending,
            'requested_by' => User::factory(),
            'sent_at' => null,
            'response' => null,
            'failed_reason' => null,
        ];
    }

    public function queued(): static
    {
        return $this->state(['status' => DeviceCommandStatus::Queued]);
    }

    public function sent(): static
    {
        return $this->state(['status' => DeviceCommandStatus::Sent, 'sent_at' => now()]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => DeviceCommandStatus::Failed,
            'failed_reason' => 'SMS gateway unreachable',
        ]);
    }
}
