<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `placement` to nav_links so the same Filament CMS resource can
 * drive both the top header (existing behaviour) and the site footer's
 * Quick Links / Support Links / Legal sections.
 *
 * Values:
 *   header           — main top-nav (existing)
 *   footer_quick     — Quick Links column in SiteFooter
 *   footer_support   — Support column in SiteFooter
 *   footer_legal     — Legal column in SiteFooter
 *
 * Existing rows default to `header` so the seeded top nav keeps working
 * unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nav_links', function (Blueprint $table) {
            $table->string('placement', 32)
                ->default('header')
                ->after('target');

            $table->index(['placement', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('nav_links', function (Blueprint $table) {
            $table->dropIndex(['placement', 'is_active', 'sort_order']);
            $table->dropColumn('placement');
        });
    }
};
