<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `original_model` on device_types (Workstream G / 2d). The hardware model identifier that the
 * DriverRegistry (package-drivers) maps to a decoder class — replaces the per-type driver_class/
 * driver_id + Driver model. Synced from the Sanity DeviceType doc. Additive.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('device_types') || Schema::hasColumn('device_types', 'original_model')) {
            return;
        }

        Schema::table('device_types', function (Blueprint $table) {
            $table->string('original_model', 64)->nullable()->after('slug')->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('device_types') && Schema::hasColumn('device_types', 'original_model')) {
            Schema::table('device_types', function (Blueprint $table) {
                $table->dropColumn('original_model');
            });
        }
    }
};
