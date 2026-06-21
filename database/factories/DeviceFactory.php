<?php

namespace TrackAnyDevice\Core\Database\Factories;

use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Models\DeviceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'device_type_id' => DeviceType::factory(),
            'imei' => $this->faker->unique()->numerify('###############'),
            'sim_number' => $this->faker->unique()->numerify('92###########'),
            'password' => '123456',
            'name' => $this->faker->words(2, true),
            'status' => 'pending',
            'battery_level' => null,
            'last_lat' => null,
            'last_lon' => null,
            'last_seen_at' => null,
        ];
    }
}
