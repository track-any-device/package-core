<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_api_keys', function (Blueprint $table): void {
            $table->id();

            // The tenant this key grants machine access for.
            // Deleting a tenant revokes all its keys automatically.
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            // Human-readable label so central staff can distinguish keys
            // in the Filament key manager (e.g. "Default", "On-Premise").
            $table->string('name')->default('Default');

            // bcrypt hash of the plain key. The plain text is shown exactly
            // once (in a Filament flash) and never stored. Validation uses
            // Hash::check($raw, $key_hash).
            $table->string('key_hash');

            // Updated at most once per minute on successful validation to
            // track active portals without causing write contention.
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            // The common lookup path: "find all valid keys for this tenant"
            // used by ValidateTenantApiKey middleware.
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_api_keys');
    }
};
