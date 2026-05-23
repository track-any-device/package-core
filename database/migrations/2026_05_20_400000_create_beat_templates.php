<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Beat templates — reusable polygon shapes admins curate from any
 * beat a user (or tenant) has drawn.
 *
 * Lifecycle:
 *   1. User / tenant_user draws a polygon on /my-beats or /beats.
 *   2. Admin sees the beat in Filament, likes it, hits
 *      "Record as template" → the polygon is captured into a new
 *      beat_templates row.
 *   3. Other users / tenants creating a beat can pick the template
 *      from a dropdown to start with the same shape.
 *   4. If the admin tweaks the template (or pushes a beat's updated
 *      polygon back into it), every beat with the matching
 *      beat_template_id is re-synced to the template's coordinates.
 *
 * Templates are global — they aren't tenant-scoped — so a curated
 * library of common shapes (school zones, police precincts, district
 * boundaries) can serve every tenant and central user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beat_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('geo_fence_type', ['polygon', 'circle', 'hexagon'])->default('polygon');
            $table->json('coordinates');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('source_beat_id')->nullable()->constrained('beats')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['is_active', 'name']);
        });

        Schema::table('beats', function (Blueprint $table) {
            $table->foreignId('beat_template_id')
                ->nullable()
                ->after('coordinates')
                ->constrained('beat_templates')
                ->nullOnDelete();

            $table->index('beat_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('beats', function (Blueprint $table) {
            $table->dropForeign(['beat_template_id']);
            $table->dropIndex(['beat_template_id']);
            $table->dropColumn('beat_template_id');
        });

        Schema::dropIfExists('beat_templates');
    }
};
