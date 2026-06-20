<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the staff_department_user pivot — the department dimension is gone under the single-Role
 * model (Workstream F). Runs after migrate_to_single_role, which already mapped the rows to roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('staff_department_user');
    }

    public function down(): void
    {
        // Not restored — see 2026_06_05_000000_create_departmental_system_tables for the original shape.
    }
};
