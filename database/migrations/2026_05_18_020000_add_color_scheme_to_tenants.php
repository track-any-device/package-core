<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds per-tenant `color_scheme` so each tenant can pick from the 21 named
 * schemes (default, neutral, slate, gray, red, blue, green, purple, orange,
 * rose, sky, yellow, fuchsia, amber, pink, lime, cyan, emerald, violet,
 * teal, indigo).
 *
 * `default` is the safe fallback and the only value allowed on the central
 * host (enforced in TrackAnyDevice\Core\Support\ThemeResolver).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'color_scheme')) {
                $table->string('color_scheme', 32)
                    ->default('default')
                    ->after('primary_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('color_scheme');
        });
    }
};
