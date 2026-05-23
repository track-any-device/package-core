<?php

namespace TrackAnyDevice\Core\Database\Factories;

use TrackAnyDevice\Core\Models\Tenant;
use TrackAnyDevice\Core\Models\TenantStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numerify('###'),
            'app_name' => null,
            'logo_path' => null,
            'primary_color' => null,
            'type' => 'portal',
            'status' => TenantStatus::Approved,
            'approved_at' => now(),
            'metadata' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => TenantStatus::Pending,
            'approved_at' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => TenantStatus::Rejected,
            'approved_at' => null,
        ]);
    }
}
