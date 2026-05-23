<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive changes that piggy-back on the existing central schema:
 *
 *  - tenants.interface_mode      → 'default' | 'no_org'
 *  - tenants.google_maps_api_key → per-tenant Google Maps key (nullable)
 *  - device_orders.user_id       → end-user who placed the order
 *  - user_devices pivot          → end-user ↔ device follow / ownership
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'interface_mode')) {
                $table->enum('interface_mode', ['default', 'no_org'])
                    ->default('default')
                    ->after('type');
            }

            if (! Schema::hasColumn('tenants', 'google_maps_api_key')) {
                $table->string('google_maps_api_key')->nullable()->after('primary_color');
            }
        });

        Schema::table('device_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('device_orders', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained()
                    ->nullOnDelete();

                $table->index('user_id');
            }
        });

        if (! Schema::hasTable('user_devices')) {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('relationship')->default('owner')
                ->comment('owner, follower, custodian');
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_id']);
            $table->index('device_id');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');

        Schema::table('device_orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['interface_mode', 'google_maps_api_key']);
        });
    }
};
