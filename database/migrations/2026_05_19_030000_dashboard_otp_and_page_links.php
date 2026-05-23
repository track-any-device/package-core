<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cross-cutting schema additions:
 *
 *  - users.last_otp_validated_on
 *      Timestamp of the most recent successful SMS OTP challenge. Used by
 *      SmsChallengeController to auto-restore the session 2FA flag when a
 *      user logs back in within 15 minutes on the same browser.
 *
 *  - solutions.page_id, device_types.page_id
 *      Optional FK to the CMS `pages` table so a Solution or a Product
 *      (DeviceType) can carry its own ordered PageSection content for
 *      public-facing /solutions/{slug} and /products/{slug} routes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_otp_validated_on')->nullable()->after('two_factor_confirmed_at');
        });

        Schema::table('solutions', function (Blueprint $table) {
            $table->foreignId('page_id')->nullable()->after('id')->constrained('pages')->nullOnDelete();
        });

        Schema::table('device_types', function (Blueprint $table) {
            $table->foreignId('page_id')->nullable()->after('id')->constrained('pages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('device_types', function (Blueprint $table) {
            $table->dropForeign(['page_id']);
            $table->dropColumn('page_id');
        });

        Schema::table('solutions', function (Blueprint $table) {
            $table->dropForeign(['page_id']);
            $table->dropColumn('page_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_otp_validated_on');
        });
    }
};
