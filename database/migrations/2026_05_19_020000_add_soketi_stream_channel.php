<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extend the stream_channel enum on drivers + device_types to include
 * 'soketi' for TAD101 (WebSocket / Pusher-compatible) devices.
 *
 * MySQL: ALTER the enum definition.
 * SQLite / other: no-op because tests use sqlite_in_memory and we never
 * created the enum as a CHECK constraint there.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('drivers') || ! Schema::hasTable('device_types')) {
            return;
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            // SQLite/PostgreSQL don't have a fixed enum we need to widen.
            return;
        }

        DB::statement("ALTER TABLE drivers MODIFY COLUMN stream_channel ENUM('jt808','gt06','h02','gps103','soketi','none') NOT NULL DEFAULT 'none'");
        DB::statement("ALTER TABLE device_types MODIFY COLUMN stream_channel ENUM('jt808','gt06','h02','gps103','soketi','none') NOT NULL DEFAULT 'none'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('drivers') || ! Schema::hasTable('device_types')) {
            return;
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            return;
        }

        DB::statement("UPDATE drivers SET stream_channel = 'none' WHERE stream_channel = 'soketi'");
        DB::statement("UPDATE device_types SET stream_channel = 'none' WHERE stream_channel = 'soketi'");
        DB::statement("ALTER TABLE drivers MODIFY COLUMN stream_channel ENUM('jt808','gt06','h02','gps103','none') NOT NULL DEFAULT 'none'");
        DB::statement("ALTER TABLE device_types MODIFY COLUMN stream_channel ENUM('jt808','gt06','h02','gps103','none') NOT NULL DEFAULT 'none'");
    }
};
