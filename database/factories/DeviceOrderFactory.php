<?php

namespace TrackAnyDevice\Core\Database\Factories;

use TrackAnyDevice\Core\Enums\DeviceOrderStatus;
use TrackAnyDevice\Core\Models\DeviceOrder;
use TrackAnyDevice\Core\Models\DeviceType;
use TrackAnyDevice\Core\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceOrder>
 */
class DeviceOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'device_type_id' => DeviceType::factory(),
            'device_id' => null,
            'status' => DeviceOrderStatus::Pending,
            'notes' => $this->faker->optional()->sentence(),
            'admin_notes' => null,
            'confirmed_by' => null,
            'confirmed_at' => null,
            'delivered_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => DeviceOrderStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn () => [
            'status' => DeviceOrderStatus::Delivered,
            'confirmed_at' => now()->subDay(),
            'delivered_at' => now(),
        ]);
    }
}
