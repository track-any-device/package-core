<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Models\AssigneeType;
use Illuminate\Database\Seeder;

class AssigneeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Worker',
                'slug' => 'worker',
                'icon_color' => '#3B82F6',
                'description' => 'A field worker carrying a GPS device.',
                'is_active' => true,
                'sort_order' => 1,
                'fields_schema' => [
                    ['key' => 'phone', 'label' => 'Phone Number', 'type' => 'string', 'required' => false],
                    ['key' => 'department', 'label' => 'Department', 'type' => 'string', 'required' => false],
                    ['key' => 'designation', 'label' => 'Designation', 'type' => 'string', 'required' => false],
                ],
            ],
            [
                'name' => 'Vehicle',
                'slug' => 'vehicle',
                'icon_color' => '#10B981',
                'description' => 'A vehicle fitted with a GPS tracking device.',
                'is_active' => true,
                'sort_order' => 2,
                'fields_schema' => [
                    ['key' => 'licence_plate', 'label' => 'Licence Plate', 'type' => 'string', 'required' => true],
                    ['key' => 'make', 'label' => 'Make', 'type' => 'string', 'required' => false],
                    ['key' => 'model', 'label' => 'Model', 'type' => 'string', 'required' => false],
                    ['key' => 'colour', 'label' => 'Colour', 'type' => 'string', 'required' => false],
                ],
            ],
        ];

        foreach ($types as $type) {
            AssigneeType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
