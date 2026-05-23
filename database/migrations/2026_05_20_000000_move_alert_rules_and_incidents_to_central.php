<?php

declare(strict_types=1);

use TrackAnyDevice\Core\Enums\IncidentPriority;
use TrackAnyDevice\Core\Enums\IncidentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidates alert_rules and incidents into the central database.
 *
 * Previously these tables lived in per-tenant databases, which required
 * MySQL CREATE DATABASE privileges and a tenant-DB provisioning step.
 * They now live centrally with a nullable tenant_id column; tenant
 * isolation is enforced at the query layer via TenantScope.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('event_type');
            $table->string('device_type_slug')->nullable();
            $table->string('scope')->default('all');
            $table->string('priority')->default(IncidentPriority::High->value);
            $table->boolean('is_enabled')->default(true);
            $table->json('condition')->nullable();
            $table->json('notification_channels')->nullable();
            $table->json('escalation_rules')->nullable();
            $table->timestamps();

            $table->index('event_type');
            $table->index('is_enabled');
            $table->index('tenant_id');
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('alert_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_id')->constrained()->restrictOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('beat_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type');
            $table->string('priority')->default(IncidentPriority::High->value);
            $table->string('status')->default(IncidentStatus::Open->value);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('triggered_at');
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedInteger('reopen_count')->default(0);
            $table->json('reopen_history')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['device_id', 'triggered_at']);
            $table->index(['tenant_id', 'triggered_at']);
            $table->index('triggered_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('alert_rules');
    }
};
