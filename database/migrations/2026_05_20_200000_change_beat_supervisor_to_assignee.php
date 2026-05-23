<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The "supervisor" concept on a beat is now the field-side leader — an
 * Assignee, not a tenant operator. Repurposes the existing
 * beats.supervisor_id column to reference the assignees table.
 *
 * Existing rows: any beat that had a User as supervisor is reset to NULL.
 * Tenants re-pick an Assignee supervisor from the beats page.
 *
 * Beat-operator access (which tenant_users can see / manage a beat) is
 * still tracked through the beat_user pivot — independent of supervision.
 *
 * For personal beats (beats.user_id), the owning user is implicitly the
 * supervisor; no separate column is needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beats', function (Blueprint $table) {
            // SQLite (used in tests) can't drop FKs by column lookup with
            // dropForeign(['col']) when the FK was created by name only.
            // Wrap in try/catch so the migration is idempotent across
            // both drivers.
            try {
                $table->dropForeign(['supervisor_id']);
            } catch (Throwable) {
                // No FK to drop — proceed.
            }
        });

        // Reset orphaned references so the new FK constraint can attach.
        DB::table('beats')->update(['supervisor_id' => null]);

        Schema::table('beats', function (Blueprint $table) {
            $table->foreign('supervisor_id')
                ->references('id')
                ->on('assignees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('beats', function (Blueprint $table) {
            try {
                $table->dropForeign(['supervisor_id']);
            } catch (Throwable) {
                // ignore
            }
        });

        DB::table('beats')->update(['supervisor_id' => null]);

        Schema::table('beats', function (Blueprint $table) {
            $table->foreign('supervisor_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
