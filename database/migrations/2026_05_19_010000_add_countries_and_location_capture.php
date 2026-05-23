<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Countries + location capture + product image columns.
 *
 *  - `countries` central table: formatting, currency, conversion rate,
 *    timezone, country dial code, SMS gateway selection.
 *  - `gsm_networks` gains `country_id`.
 *  - `users` gains browser-reported coords + IP + consent timestamp +
 *    `country_id` for mobile-number formatting.
 *  - `products` gains `image` + `images` so Product can take over the
 *    pricing/media role from DeviceType.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Countries ─────────────────────────────────────────────────────
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('iso_code', 2)->unique();
            $table->string('country_code', 5);
            $table->string('currency');
            $table->string('currency_code', 3);
            $table->enum('code_prepend_or_postpend', ['prepend', 'postpend'])->default('prepend');
            $table->string('thousands_separator', 1)->default(',');
            $table->string('hundreds_separator', 1)->default(',');
            $table->unsignedTinyInteger('decimal_values')->default(2);
            $table->string('decimal_separator', 1)->default('.');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_fallback')->default(false);
            $table->string('timezone', 64);
            $table->string('sms_gateway')->nullable();
            $table->decimal('conversion_rate', 14, 6)->default(1);
            $table->decimal('conversion_markup_percent', 5, 2)->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_default');
            $table->index('is_fallback');
            $table->index('sms_gateway');
        });

        // ── 2. gsm_networks.country_id ──────────────────────────────────────
        Schema::table('gsm_networks', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('country_code')->constrained()->nullOnDelete();
        });

        // ── 3. users: location + consent + mobile country ───────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('browser_latitude', 10, 7)->nullable()->after('display_timezone');
            $table->decimal('browser_longitude', 10, 7)->nullable()->after('browser_latitude');
            $table->timestamp('location_consented_at')->nullable()->after('browser_longitude');
            $table->string('last_ip_address', 45)->nullable()->after('location_consented_at');
            $table->string('ip_country_code', 2)->nullable()->after('last_ip_address');
            $table->foreignId('mobile_country_id')->nullable()->after('ip_country_code')->constrained('countries')->nullOnDelete();
        });

        // ── 4. products: media columns ──────────────────────────────────────
        Schema::table('products', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
            $table->json('images')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['image', 'images']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['mobile_country_id']);
            $table->dropColumn([
                'browser_latitude', 'browser_longitude', 'location_consented_at',
                'last_ip_address', 'ip_country_code', 'mobile_country_id',
            ]);
        });

        Schema::table('gsm_networks', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropColumn('country_id');
        });

        Schema::dropIfExists('countries');
    }
};
