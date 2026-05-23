<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant incident taxonomy: priorities, statuses, and levels.
 *
 * Each table carries a nullable tenant_id. Rows with tenant_id = NULL
 * are platform-wide defaults that every tenant inherits when they
 * haven't defined their own. As soon as a tenant adds any row, the
 * defaults are hidden for that tenant (overrides win).
 *
 * Constraints:
 *   - Every tenant set MUST contain exactly one row with is_open = true
 *     (the entry status) and at least one row with is_closed = true
 *     (a terminal status). Other rows are "in-flight" intermediate
 *     statuses with both flags false. The seeder enforces this for
 *     tenant_id = NULL; the tenant settings UI enforces it for
 *     per-tenant rows.
 *   - incident_levels are integer-ranked; level_number 1 is the
 *     baseline "out of leaf beat" state. Higher numbers represent
 *     escalations as the device exits each ancestor in the chain.
 *
 * incidents.level is added here too so the level-aware violation
 * detector has somewhere to write.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_priorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key', 40);
            $table->string('label', 80);
            $table->string('color', 32)->default('gray');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
            $table->index(['tenant_id', 'sort_order']);
        });

        Schema::create('incident_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key', 40);
            $table->string('label', 80);
            $table->string('color', 32)->default('gray');
            $table->unsignedSmallInteger('sort_order')->default(0);
            // Exactly one row per (tenant_id) carries is_open = true
            // (the entry status). At least one row carries is_closed =
            // true (terminal). Multiple is_closed rows are allowed —
            // e.g. "resolved" and "dismissed" both count as closed.
            $table->boolean('is_open')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
            $table->index(['tenant_id', 'sort_order']);
        });

        Schema::create('incident_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('level_number');
            $table->string('label', 80);
            $table->string('color', 32)->default('warning');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'level_number']);
            $table->index('tenant_id');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->unsignedSmallInteger('level')->default(1)->after('priority');
            $table->index(['tenant_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'level']);
            $table->dropColumn('level');
        });

        Schema::dropIfExists('incident_levels');
        Schema::dropIfExists('incident_statuses');
        Schema::dropIfExists('incident_priorities');
    }
};
