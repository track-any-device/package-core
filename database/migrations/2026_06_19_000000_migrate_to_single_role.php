<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Single-Role data migration (Workstream F). Maps the old two-dimensional model
 * (users.role + staff_department_user.department/is_workshop) onto the new single `role`:
 *
 *   admin                                              -> admin
 *   supervisor|staff + department = procurement        -> procurement
 *   supervisor|staff + department = warehouse & workshop -> workshop
 *   supervisor|staff (everything else, incl. core_team/
 *     engineering/marketing/sales/warehouse-non-workshop) -> core
 *   tenant_user / user                                 -> unchanged
 *
 * Does NOT drop `staff_department_user` yet — package-admin still references StaffDepartment.
 * A later migration drops the pivot + the deprecated enum cases once package-admin is updated.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $hasDept = Schema::hasTable('staff_department_user');

        $legacy = DB::table('users')->whereIn('role', ['supervisor', 'staff'])->pluck('id');

        foreach ($legacy as $id) {
            $new = 'core';

            if ($hasDept) {
                $rows = DB::table('staff_department_user')->where('user_id', $id)->get();
                foreach ($rows as $r) {
                    if (($r->department ?? null) === 'procurement') {
                        $new = 'procurement';
                        break;
                    }
                    if (($r->department ?? null) === 'warehouse' && (int) ($r->is_workshop ?? 0) === 1) {
                        $new = 'workshop';
                        break;
                    }
                }
            }

            DB::table('users')->where('id', $id)->update(['role' => $new]);
        }
    }

    public function down(): void
    {
        // Best-effort rollback: the new operational roles collapse back to legacy 'staff'.
        // Department pivot rows are untouched by this migration, so they remain intact.
        DB::table('users')
            ->whereIn('role', ['core', 'procurement', 'workshop', 'delivery_order'])
            ->update(['role' => 'staff']);
    }
};
