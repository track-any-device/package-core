<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TAD-1.0.0 schema additions.
 *
 *  - New catalogue tables: gsm_networks, chips, compute_boards,
 *    connecting_cables, charging_sets, products, drivers.
 *  - Pivots linking device_types to the new catalogue tables.
 *  - Column additions on sensors, device_types, device_type_sensor, devices.
 *  - users.display_timezone for per-user TZ rendering.
 *  - chip_sensor pivot (which sensors a chip can produce).
 *
 * Telemetry (signals) is intentionally NOT a MySQL table — signals are
 * written as InfluxDB points via SignalService.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. GSM Networks ──────────────────────────────────────────────────
        Schema::create('gsm_networks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country_code', 3);
            $table->string('apn');
            $table->string('apn_username')->nullable();
            $table->string('apn_password')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('country_code');
            $table->index('is_active');
        });

        // ── 2. Chips ─────────────────────────────────────────────────────────
        Schema::create('chips', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('manufacturer');
            $table->enum('type', ['gnss', 'cellular', 'bluetooth', 'wifi', 'mcu', 'combo']);
            $table->string('datasheet_url')->nullable();
            $table->json('specifications')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('type');
        });

        // ── 3. Chip ↔ Sensor pivot ───────────────────────────────────────────
        Schema::create('chip_sensor', function (Blueprint $table) {
            $table->foreignId('chip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sensor_id')->constrained()->cascadeOnDelete();
            $table->primary(['chip_id', 'sensor_id']);
        });

        // ── 4. Compute Boards ────────────────────────────────────────────────
        Schema::create('compute_boards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('manufacturer');
            $table->string('mcu')->nullable();
            $table->unsignedInteger('flash_kb')->nullable();
            $table->unsignedInteger('ram_kb')->nullable();
            $table->decimal('operating_voltage', 4, 2)->nullable();
            $table->string('datasheet_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── 5. Connecting Cables ─────────────────────────────────────────────
        Schema::create('connecting_cables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('connector_type');
            $table->enum('protocol', ['uart', 'usb', 'jtag', 'swd']);
            $table->json('baud_rates')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── 6. Charging Sets ─────────────────────────────────────────────────
        Schema::create('charging_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('connector');
            $table->decimal('voltage', 4, 2);
            $table->unsignedInteger('current_ma');
            $table->boolean('wireless')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── 7. Drivers ───────────────────────────────────────────────────────
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('class')->unique();
            $table->enum('stream_channel', ['jt808', 'gt06', 'h02', 'gps103', 'none'])->default('none');
            $table->boolean('supports_gsm_commands')->default(true);
            $table->boolean('supports_stream')->default(false);
            $table->string('version')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── 8. Products (morphic catalogue) ──────────────────────────────────
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->enum('product_type', ['device_type', 'chip', 'compute_board', 'connecting_cable', 'charging_set']);
            $table->morphs('productable');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('PKR');
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('product_type');
            $table->index('is_active');
        });

        // ── 9. Sensor column additions ───────────────────────────────────────
        Schema::table('sensors', function (Blueprint $table) {
            $table->string('label')->nullable()->after('name');
            $table->enum('data_type', ['float', 'integer', 'boolean', 'coordinate', 'string'])
                ->default('float')
                ->after('unit');
            $table->string('icon')->nullable()->after('description');
        });

        // Backfill label = name for existing rows (works on MySQL + SQLite).
        if (Schema::hasTable('sensors')) {
            DB::table('sensors')
                ->whereNull('label')
                ->update(['label' => DB::raw('name')]);
        }

        // ── 10. device_types column additions ────────────────────────────────
        Schema::table('device_types', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->after('slug')->constrained()->nullOnDelete();
            $table->string('manual_url')->nullable()->after('image');
            $table->string('protocol_url')->nullable()->after('manual_url');
            $table->string('default_password', 32)->default('123456')->after('protocol_url');
            $table->enum('stream_channel', ['jt808', 'gt06', 'h02', 'gps103', 'none'])
                ->default('none')
                ->after('default_password');
            $table->json('meta')->nullable()->after('stream_channel');
        });

        // ── 11. device_type_sensor: add is_primary ──────────────────────────
        Schema::table('device_type_sensor', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('sensor_id');
        });

        // ── 12. New pivots ──────────────────────────────────────────────────
        Schema::create('device_type_chip', function (Blueprint $table) {
            $table->foreignId('device_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chip_id')->constrained()->cascadeOnDelete();
            $table->primary(['device_type_id', 'chip_id']);
        });

        Schema::create('device_type_compute_board', function (Blueprint $table) {
            $table->foreignId('device_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('compute_board_id')->constrained()->cascadeOnDelete();
            $table->primary(['device_type_id', 'compute_board_id']);
        });

        Schema::create('device_type_connecting_cable', function (Blueprint $table) {
            $table->foreignId('device_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('connecting_cable_id')->constrained()->cascadeOnDelete();
            $table->primary(['device_type_id', 'connecting_cable_id']);
        });

        Schema::create('device_type_charging_set', function (Blueprint $table) {
            $table->foreignId('device_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charging_set_id')->constrained()->cascadeOnDelete();
            $table->primary(['device_type_id', 'charging_set_id']);
        });

        // ── 13. devices column additions ────────────────────────────────────
        Schema::table('devices', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->after('device_type_id')->constrained()->nullOnDelete();
            $table->string('serial_number', 64)->nullable()->after('imei')->unique();
            $table->foreignId('gsm_network_id')->nullable()->after('gsm_number')->constrained()->nullOnDelete();
            $table->string('iccid', 22)->nullable()->after('gsm_network_id');
            $table->string('firmware_version')->nullable()->after('iccid');
            $table->enum('onboarding_status', ['pending', 'sim_added', 'configured', 'verified'])
                ->default('pending')
                ->after('status');
            $table->timestamp('last_signal_at')->nullable()->after('last_seen_at');
            $table->foreignId('user_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('user_id');

            $table->softDeletes();

            $table->index('onboarding_status');
            $table->index(['tenant_id', 'user_id']);
        });

        // ── 14. users.display_timezone ──────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->string('display_timezone', 64)->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('display_timezone');
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['tenant_id', 'user_id']);
            $table->dropIndex(['onboarding_status']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['gsm_network_id']);
            $table->dropForeign(['driver_id']);
            $table->dropUnique(['serial_number']);
            $table->dropColumn([
                'driver_id', 'serial_number', 'gsm_network_id', 'iccid', 'firmware_version',
                'onboarding_status', 'last_signal_at', 'user_id', 'assigned_at',
            ]);
        });

        Schema::dropIfExists('device_type_charging_set');
        Schema::dropIfExists('device_type_connecting_cable');
        Schema::dropIfExists('device_type_compute_board');
        Schema::dropIfExists('device_type_chip');

        Schema::table('device_type_sensor', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });

        Schema::table('device_types', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn(['driver_id', 'manual_url', 'protocol_url', 'default_password', 'stream_channel', 'meta']);
        });

        Schema::table('sensors', function (Blueprint $table) {
            $table->dropColumn(['label', 'data_type', 'icon']);
        });

        Schema::dropIfExists('products');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('charging_sets');
        Schema::dropIfExists('connecting_cables');
        Schema::dropIfExists('compute_boards');
        Schema::dropIfExists('chip_sensor');
        Schema::dropIfExists('chips');
        Schema::dropIfExists('gsm_networks');
    }
};
