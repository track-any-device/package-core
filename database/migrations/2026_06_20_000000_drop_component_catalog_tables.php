<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog cut (2026-06). DESTRUCTIVE — minor version bump.
 *
 * Removes the obsolete component catalogue. The sellable catalog is now DeviceType (app, FK target
 * for devices + driver resolution) plus Accessory/CMS content in Sanity. Drops the 5 device_type_*
 * build-spec pivots first, then the 6 component tables. `products` and `device_types` are retained.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            foreach ([
                // pivots / children first
                'chip_sensor',
                'device_sensor',
                'device_type_chip',
                'device_type_compute_board',
                'device_type_connecting_cable',
                'device_type_charging_set',
                'device_type_sensor',
                // parent component tables
                'chips',
                'compute_boards',
                'connecting_cables',
                'charging_sets',
                'sensors',
                'gsm_networks',
            ] as $table) {
                Schema::dropIfExists($table);
            }
        });
    }

    public function down(): void
    {
        // Not restored — see create_app_schema / tad_schema_v1 for the original table shapes.
    }
};
