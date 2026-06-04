<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public PKCE clients (e.g. the TAD101 mobile app) have no client secret
 * by design. The original NOT NULL constraint prevents seeding the mobile
 * client on existing installations where the table was already created.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            if (Schema::hasColumn('oauth_clients', 'client_secret_hash')) {
                $table->string('client_secret_hash')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('client_secret_hash')->nullable(false)->change();
        });
    }
};
