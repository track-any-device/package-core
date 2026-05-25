<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('auth_layout', 32)->default('split')->after('registration_enabled');
            $table->string('auth_background_path')->nullable()->after('auth_layout');
            $table->string('auth_login_title', 120)->nullable()->after('auth_background_path');
            $table->string('auth_login_description', 255)->nullable()->after('auth_login_title');
            $table->string('auth_register_title', 120)->nullable()->after('auth_login_description');
            $table->string('auth_register_description', 255)->nullable()->after('auth_register_title');
            $table->string('auth_forgot_title', 120)->nullable()->after('auth_register_description');
            $table->string('auth_forgot_description', 255)->nullable()->after('auth_forgot_title');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'auth_layout',
                'auth_background_path',
                'auth_login_title',
                'auth_login_description',
                'auth_register_title',
                'auth_register_description',
                'auth_forgot_title',
                'auth_forgot_description',
            ]);
        });
    }
};
