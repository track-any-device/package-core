<?php

namespace TrackAnyDevice\Core\Database\Factories;

use TrackAnyDevice\Core\Enums\BeatStatus;
use TrackAnyDevice\Core\Enums\GeoFenceType;
use TrackAnyDevice\Core\Models\Beat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Beat>
 */
class BeatFactory extends Factory
{
    public function definition(): array
    {
        $lat = $this->faker->latitude(30, 34);
        $lon = $this->faker->longitude(70, 75);

        return [
            'parent_id' => null,
            'name' => $this->faker->words(2, true).' Beat',
            'description' => $this->faker->sentence(),
            'geo_fence_type' => GeoFenceType::Polygon,
            'coordinates' => [
                ['lat' => $lat, 'lng' => $lon],
                ['lat' => $lat + 0.01, 'lng' => $lon],
                ['lat' => $lat + 0.01, 'lng' => $lon + 0.01],
                ['lat' => $lat, 'lng' => $lon + 0.01],
            ],
            'supervisor_id' => null,
            'status' => BeatStatus::Active,
        ];
    }

    public function circle(): static
    {
        $lat = $this->faker->latitude(30, 34);
        $lon = $this->faker->longitude(70, 75);

        return $this->state([
            'geo_fence_type' => GeoFenceType::Circle,
            'coordinates' => ['lat' => $lat, 'lng' => $lon, 'radius' => 500],
        ]);
    }
}
