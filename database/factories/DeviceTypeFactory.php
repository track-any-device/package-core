<?php

namespace TrackAnyDevice\Core\Database\Factories;

use TrackAnyDevice\Core\Models\DeviceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceType>
 */
class DeviceTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->unique()->slug(),
            'driver_class' => 'App\\Drivers\\TestDriver',
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'configuration_schema' => null,
        ];
    }
}
