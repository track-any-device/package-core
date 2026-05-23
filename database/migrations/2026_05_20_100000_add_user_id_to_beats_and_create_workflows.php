<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds personal-user ownership to beats and introduces the workflow engine.
 *
 * Ownership rule on beats: exactly one of {tenant_id, user_id} is set. The
 * application enforces this in the Beat model (boot-time guard) rather than
 * a DB CHECK constraint to keep MySQL/SQLite parity for tests.
 *
 * Workflows are tenant-scoped OR user-scoped (mirroring beats). The graph
 * column stores the React Flow node/edge JSON the visual designer emits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beats', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('user_id');
        });

        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type', 32);
            $table->json('trigger_config')->nullable();
            $table->json('graph');
            $table->boolean('is_enabled')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_enabled']);
            $table->index(['user_id', 'is_enabled']);
            $table->index(['trigger_type', 'is_enabled']);
        });

        Schema::create('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('incident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('triggered_by', 64);
            $table->json('input_context')->nullable();
            $table->string('status', 16)->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['workflow_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('incident_id');
        });

        Schema::create('workflow_step_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_run_id')->constrained()->cascadeOnDelete();
            $table->string('node_id', 64);
            $table->string('action_type', 32);
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->string('status', 16)->default('pending');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['workflow_run_id', 'executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_step_logs');
        Schema::dropIfExists('workflow_runs');
        Schema::dropIfExists('workflows');

        Schema::table('beats', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
