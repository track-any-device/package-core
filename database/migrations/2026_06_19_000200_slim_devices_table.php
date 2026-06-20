<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Slim the `devices` table (Workstream G, 2026-06-19). DESTRUCTIVE — minor version bump.
 *
 * Keeps: id, name, imei, sim_number, gsm_number, password, device_type_id (FK → maps to the Sanity
 * catalog), tenant_id, user_id, status, last_lat, last_lon, battery_level, last_seen_at, metadata.
 * Adds: broadcast_id (device-emitted id used for realtime routing), apn_settings (json network config).
 * Drops: serial_number, iccid, firmware_version, driver_id (→ resolved by originalModel, 2b),
 *   gsm_network_id, warehouse_id, onboarding_status, is_approved, is_visible_to_tenant,
 *   last_signal_at, last_update_requested_at, connection_attempt_count, next_connection_attempt_at,
 *   assigned_at, map_icon. Status collapses to active|blocked|pending (string col, app-enum enforced).
 * Also retires the `dummy_device_sms_log` simulator table.
 *
 * Defensive (hasColumn guards) so it is safe regardless of the exact current column set.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('devices')) {
            return;
        }

        // 1) additive columns
        Schema::table('devices', function (Blueprint $table) {
            if (! Schema::hasColumn('devices', 'broadcast_id')) {
                $table->string('broadcast_id', 64)->nullable()->after('imei')->index();
            }
            if (! Schema::hasColumn('devices', 'apn_settings')) {
                $table->json('apn_settings')->nullable();
            }
        });

        // 2) status → plain string so we can remap freely (app enforces the enum via DeviceStatus)
        Schema::table('devices', function (Blueprint $table) {
            $table->string('status', 16)->default('pending')->change();
        });
        DB::table('devices')->whereIn('status', ['available', 'assigned', 'in_service'])->update(['status' => 'active']);
        DB::table('devices')->whereIn('status', ['maintenance', 'lost', 'retired'])->update(['status' => 'blocked']);
        DB::table('devices')->whereNotIn('status', ['active', 'blocked'])->update(['status' => 'pending']);

        // 2b) drop indexes on columns we're about to remove. MySQL auto-drops a column's indexes
        //     when the column is dropped, but SQLite (and portability) need them removed explicitly.
        Schema::table('devices', function (Blueprint $table) {
            if (Schema::hasColumn('devices', 'serial_number') && Schema::hasIndex('devices', 'devices_serial_number_unique')) {
                $table->dropUnique('devices_serial_number_unique');
            }
            if (Schema::hasColumn('devices', 'onboarding_status') && Schema::hasIndex('devices', 'devices_onboarding_status_index')) {
                $table->dropIndex('devices_onboarding_status_index');
            }
            if (Schema::hasIndex('devices', 'devices_offline_detection_idx')) {
                $table->dropIndex('devices_offline_detection_idx');
            }
        });

        // 3) drop FK columns + retired plain columns
        Schema::table('devices', function (Blueprint $table) {
            foreach (['driver_id', 'gsm_network_id', 'warehouse_id'] as $col) {
                if (Schema::hasColumn('devices', $col)) {
                    $table->dropConstrainedForeignId($col);
                }
            }
            foreach ([
                'serial_number', 'iccid', 'firmware_version', 'onboarding_status', 'is_approved',
                'is_visible_to_tenant', 'last_signal_at', 'last_update_requested_at',
                'connection_attempt_count', 'next_connection_attempt_at', 'assigned_at', 'map_icon',
            ] as $col) {
                if (Schema::hasColumn('devices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // 4) retire the dummy simulator table
        Schema::dropIfExists('dummy_device_sms_log');
    }

    public function down(): void
    {
        // Best-effort, non-restorative: dropped columns/data and the dummy table are not recreated.
        Schema::table('devices', function (Blueprint $table) {
            foreach (['broadcast_id', 'apn_settings'] as $col) {
                if (Schema::hasColumn('devices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
