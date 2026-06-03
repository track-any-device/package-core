<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beats', function (Blueprint $table): void {
            // Violation semantics for this beat zone.
            //   inclusion — device must stay inside; incident on exit.
            //   exclusion — device must stay outside; incident on entry.
            // Defaults to 'inclusion' so all existing beats retain their
            // current behaviour without requiring a data migration.
            $table->string('zone_type')->default('inclusion')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('beats', function (Blueprint $table): void {
            $table->dropColumn('zone_type');
        });
    }
};
