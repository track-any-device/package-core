<?php

namespace TrackAnyDevice\Core\Database\Factories;

use TrackAnyDevice\Core\Models\AssigneeType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AssigneeType>
 */
class AssigneeTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'icon_path' => null,
            'icon_color' => $this->faker->hexColor(),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
            'fields_schema' => null,
        ];
    }
}
