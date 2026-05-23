<?php

namespace TrackAnyDevice\Core\Database\Factories;

use TrackAnyDevice\Core\Enums\GeoFenceType;
use TrackAnyDevice\Core\Models\BeatTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeatTemplate>
 */
class BeatTemplateFactory extends Factory
{
    protected $model = BeatTemplate::class;

    public function definition(): array
    {
        $lat = $this->faker->latitude(30, 34);
        $lon = $this->faker->longitude(70, 75);

        return [
            'name' => ucwords($this->faker->words(2, true)).' Template',
            'description' => $this->faker->sentence(),
            'geo_fence_type' => GeoFenceType::Polygon,
            'coordinates' => [
                ['lat' => $lat, 'lng' => $lon],
                ['lat' => $lat + 0.01, 'lng' => $lon],
                ['lat' => $lat + 0.01, 'lng' => $lon + 0.01],
                ['lat' => $lat, 'lng' => $lon + 0.01],
            ],
            'created_by' => null,
            'source_beat_id' => null,
            'is_active' => true,
            'version' => 1,
        ];
    }
}
