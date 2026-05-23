<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single source-of-truth migration for the bulk of the application schema.
 *
 * Incidents and alert_rules are created in a separate migration
 * (`2026_05_20_000000_move_alert_rules_and_incidents_to_central.php`) so
 * their per-tenant -> central move is traceable in the migration history.
 *
 * Tables are created in foreign-key dependency order.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Tenants ───────────────────────────────────────────────────────
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('portal');
            $table->string('app_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('primary_color', 7)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // ── 2. Domains ───────────────────────────────────────────────────────
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('domain')->unique();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['domain', 'tenant_id']);
        });

        // ── 3. Tenant ↔ User pivot (membership of tenant_user accounts) ──────
        Schema::create('tenant_users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['tenant_id', 'user_id']);
            $table->index('user_id');
        });

        // ── 4. Sensors ───────────────────────────────────────────────────────
        Schema::create('sensors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('unit')->nullable();
            $table->string('protocol')->nullable()->comment('e.g. gsm_2g, gsm_3g, gsm_4g, gsm_5g');
            $table->string('description')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ── 5. Device Types ──────────────────────────────────────────────────
        Schema::create('device_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('driver_class');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->decimal('price_usd', 8, 2)->nullable();
            $table->decimal('price_pkr', 10, 2)->nullable();
            $table->unsignedSmallInteger('min_quantity')->default(1);
            $table->unsignedSmallInteger('quantity_multiple')->default(1);
            $table->unsignedSmallInteger('max_quantity')->nullable();
            $table->unsignedSmallInteger('bulk_quantity')->nullable();
            $table->string('badge_label')->nullable();
            $table->string('badge_color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('configuration_schema')->nullable();
            $table->timestamps();
        });

        // ── 6. Device Type ↔ Sensor pivot ────────────────────────────────────
        Schema::create('device_type_sensor', function (Blueprint $table) {
            $table->foreignId('device_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sensor_id')->constrained()->cascadeOnDelete();
            $table->primary(['device_type_id', 'sensor_id']);
        });

        // ── 7. Devices ───────────────────────────────────────────────────────
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_type_id')->constrained()->restrictOnDelete();
            $table->string('imei', 20)->unique();
            $table->string('sim_number', 30)->nullable()->unique();
            $table->string('gsm_number', 20)->nullable()->unique();
            $table->string('password', 20)->default('123456');
            $table->string('name');
            $table->enum('status', [
                'warehouse', 'registration', 'inventory', 'in_transit',
                'available', 'assigned', 'in_service', 'maintenance', 'lost', 'retired',
            ])->default('inventory');
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_visible_to_tenant')->default(false);
            $table->unsignedTinyInteger('battery_level')->nullable();
            $table->decimal('last_lat', 10, 7)->nullable();
            $table->decimal('last_lon', 10, 7)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_update_requested_at')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('imei');
            $table->index('tenant_id');
        });

        // ── 8. Device ↔ Sensor pivot ─────────────────────────────────────────
        Schema::create('device_sensor', function (Blueprint $table) {
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sensor_id')->constrained()->cascadeOnDelete();
            $table->primary(['device_id', 'sensor_id']);
        });

        // ── 9. Beats ─────────────────────────────────────────────────────────
        Schema::create('beats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('beats')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('geo_fence_type', ['polygon', 'circle', 'hexagon'])->default('polygon');
            $table->json('coordinates');
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('status');
            $table->index('parent_id');
            $table->index('tenant_id');
        });

        // ── 10. Beat ↔ User pivot ────────────────────────────────────────────
        Schema::create('beat_user', function (Blueprint $table) {
            $table->foreignId('beat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('beat_role')->default('staff');
            $table->primary(['beat_id', 'user_id']);
        });

        // ── 11. Assignee Types ───────────────────────────────────────────────
        Schema::create('assignee_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon_path')->nullable();
            $table->string('icon_color', 7)->default('#6B7280');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('fields_schema')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
        });

        // ── 12. Assignees ────────────────────────────────────────────────────
        Schema::create('assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assignee_type_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('status', ['active', 'inactive', 'on_leave', 'terminated'])->default('active');
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('assignee_type_id');
            $table->index('tenant_id');
        });

        // ── 13. Device Assignments ───────────────────────────────────────────
        Schema::create('device_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->restrictOnDelete();
            $table->foreignId('assignee_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('returned_at')->nullable();
            $table->string('condition_out')->nullable();
            $table->string('condition_in')->nullable();
            $table->enum('status', ['active', 'returned', 'transferred', 'lost'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'status']);
            $table->index(['assignee_id', 'status']);
        });

        // ── 14. Beat Assignments ─────────────────────────────────────────────
        Schema::create('beat_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->restrictOnDelete();
            $table->foreignId('beat_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->enum('status', ['active', 'ended', 'transferred'])->default('active');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'status']);
            $table->index(['beat_id', 'status']);
        });

        // ── 15. Device Commands ──────────────────────────────────────────────
        Schema::create('device_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('command_type');
            $table->text('command_payload');
            $table->string('channel')->default('sms');
            $table->enum('status', [
                'pending', 'queued', 'sent', 'delivered',
                'acknowledged', 'failed', 'cancelled',
            ])->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->text('response')->nullable();
            $table->text('failed_reason')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'status']);
        });

        // ── 16. Notifications ────────────────────────────────────────────────
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // ── 17. Incoming SMS ─────────────────────────────────────────────────
        Schema::create('incoming_sms', function (Blueprint $table) {
            $table->id();
            $table->text('raw_message');
            $table->string('sender_number')->index();
            $table->timestamp('received_at');
            $table->string('source')->default('gateway_api');
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_error')->nullable();
            $table->timestamps();

            $table->index(['sender_number', 'processed_at']);
            $table->index('received_at');
        });

        // ── 18. Dummy Device SMS Log ─────────────────────────────────────────
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

        // ── 19. Device Orders ────────────────────────────────────────────────
        Schema::create('device_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_type_id')->constrained();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('status')->default('pending')->index();
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // ── 20. Screens ──────────────────────────────────────────────────────
        Schema::create('screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active', 'sort_order']);
        });

        // ── 21. CMS — Nav Links ───────────────────────────────────────────────
        Schema::create('nav_links', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('href');
            $table->string('target')->default('_self');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // ── 22. CMS — Solutions ───────────────────────────────────────────────
        Schema::create('solutions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon_name')->nullable();
            $table->string('gradient_from')->default('blue-900');
            $table->string('gradient_to')->default('blue-700');
            $table->string('image_path')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_href')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // ── 23. CMS — Testimonials ────────────────────────────────────────────
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('company')->nullable();
            $table->text('quote');
            $table->string('avatar_path')->nullable();
            $table->string('avatar_initials', 4)->nullable();
            $table->string('avatar_color', 9)->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('campaign')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_featured', 'is_approved', 'sort_order']);
            $table->index('campaign');
        });

        // ── 24. CMS — Pages ───────────────────────────────────────────────────
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['slug', 'is_active']);
        });

        // ── 25. CMS — Page Sections ───────────────────────────────────────────
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('identifier')->nullable();
            $table->json('content')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['page_id', 'is_active', 'sort_order']);
        });

        // ── 26. OTP codes — phone verification & SMS 2FA challenge ───────────
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('phone', 20);
            $table->string('code', 6);
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'type']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('solutions');
        Schema::dropIfExists('nav_links');
        Schema::dropIfExists('screens');
        Schema::dropIfExists('device_orders');
        Schema::dropIfExists('dummy_device_sms_log');
        Schema::dropIfExists('incoming_sms');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('device_commands');
        Schema::dropIfExists('beat_assignments');
        Schema::dropIfExists('device_assignments');
        Schema::dropIfExists('assignees');
        Schema::dropIfExists('assignee_types');
        Schema::dropIfExists('beat_user');
        Schema::dropIfExists('beats');
        Schema::dropIfExists('device_sensor');
        Schema::dropIfExists('devices');
        Schema::dropIfExists('device_type_sensor');
        Schema::dropIfExists('device_types');
        Schema::dropIfExists('sensors');
        Schema::dropIfExists('tenant_users');
        Schema::dropIfExists('domains');
        Schema::dropIfExists('tenants');
    }
};
