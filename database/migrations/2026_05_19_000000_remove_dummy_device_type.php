<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove the Dummy device type and its supporting infrastructure.
 *
 *  - Drops dummy_device_sms_log (used by SimulateDummyDevices, deleted).
 *  - Makes device_types.driver_class nullable so catalogue-only entries
 *    (store products with no real driver yet) can omit it.
 *  - Cleans up any seeded Dummy rows in drivers / device_types.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('dummy_device_sms_log');

        Schema::table('device_types', function (Blueprint $table) {
            $table->string('driver_class')->nullable()->change();
        });

        DB::table('devices')
            ->whereIn('device_type_id', function ($q) {
                $q->select('id')->from('device_types')->where('slug', 'dummy');
            })
            ->delete();

        DB::table('device_types')->where('slug', 'dummy')->delete();
        DB::table('drivers')->where('class', 'App\\Drivers\\DummyDriver')->delete();
    }

    public function down(): void
    {
        Schema::create('dummy_device_sms_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedTinyInteger('battery_level')->default(80);
            $table->unsignedTinyInteger('signal_strength')->default(70);
            $table->boolean('is_charging')->default(false);
            $table->boolean('is_outside_beat')->default(false);
            $table->unsignedTinyInteger('ticks_outside')->default(0);
            $table->unsignedTinyInteger('ticks_until_return')->default(0);
            $table->timestamp('last_ticked_at')->nullable();
            $table->timestamps();
        });
    }
};
