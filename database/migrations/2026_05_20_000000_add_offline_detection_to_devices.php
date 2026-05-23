<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offline-device detection / recovery state.
 *
 *  - `connection_attempt_count` — number of consecutive recovery SMS attempts
 *    since the device last responded. Reset to 0 by SignalService whenever a
 *    new signal arrives. Devices with count >= MAX (see
 *    OfflineDeviceRecoveryService::MAX_ATTEMPTS) are considered unreachable
 *    and skipped by the cron until a manual reset or a fresh signal.
 *  - `next_connection_attempt_at` — exponential-backoff floor. The cron skips
 *    devices whose floor lies in the future.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedSmallInteger('connection_attempt_count')
                ->default(0)
                ->after('last_update_requested_at');
            $table->timestamp('next_connection_attempt_at')
                ->nullable()
                ->after('connection_attempt_count');

            $table->index(
                ['status', 'next_connection_attempt_at'],
                'devices_offline_detection_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex('devices_offline_detection_idx');
            $table->dropColumn(['connection_attempt_count', 'next_connection_attempt_at']);
        });
    }
};
