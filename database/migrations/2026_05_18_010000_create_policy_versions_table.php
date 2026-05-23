<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central-DB migration: versioned policy documents.
 *
 * Holds the live + every historical revision of Terms of Service, Privacy
 * Policy, and Cookie Policy. Only one row per `type` may have
 * is_current = true — enforced by the PolicyVersion model when setting a
 * new version.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_versions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['terms', 'privacy', 'cookie']);
            $table->string('version', 20);
            $table->string('title');
            $table->longText('content');
            $table->date('effective_from');
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->unique(['type', 'version']);
            $table->index(['type', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_versions');
    }
};
