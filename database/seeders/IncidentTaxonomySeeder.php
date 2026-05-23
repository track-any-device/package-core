<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Models\IncidentLevel;
use TrackAnyDevice\Core\Models\IncidentPriorityOption;
use TrackAnyDevice\Core\Models\IncidentStatusOption;
use Illuminate\Database\Seeder;

/**
 * Seeds the platform-wide default incident taxonomy.
 *
 * All rows carry tenant_id = NULL — they are the fallback used by
 * every tenant that hasn't defined their own. Tenants override the
 * full set in the tenant settings UI; partial overrides are not
 * supported (it's all-or-nothing per dimension).
 *
 * Safe to re-run: every row is keyed via updateOrCreate.
 */
class IncidentTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $this->priorities();
        $this->statuses();
        $this->levels();
    }

    private function priorities(): void
    {
        $rows = [
            ['key' => 'critical', 'label' => 'Critical', 'color' => 'danger',  'sort_order' => 10, 'is_default' => false],
            ['key' => 'high',     'label' => 'High',     'color' => 'warning', 'sort_order' => 20, 'is_default' => true],
            ['key' => 'medium',   'label' => 'Medium',   'color' => 'info',    'sort_order' => 30, 'is_default' => false],
            ['key' => 'low',      'label' => 'Low',      'color' => 'gray',    'sort_order' => 40, 'is_default' => false],
            ['key' => 'info',     'label' => 'Info',     'color' => 'gray',    'sort_order' => 50, 'is_default' => false],
        ];

        foreach ($rows as $r) {
            IncidentPriorityOption::query()->updateOrCreate(
                ['tenant_id' => null, 'key' => $r['key']],
                $r,
            );
        }
    }

    private function statuses(): void
    {
        // open and closed are global anchors — every tenant set gets a
        // row with is_open=true and one with is_closed=true. The default
        // set adds Acknowledged + Dismissed + Escalated for parity with
        // the legacy IncidentStatus enum.
        $rows = [
            ['key' => 'open',         'label' => 'Open',         'color' => 'danger',  'sort_order' => 10, 'is_open' => true,  'is_closed' => false],
            ['key' => 'acknowledged', 'label' => 'Acknowledged', 'color' => 'warning', 'sort_order' => 20, 'is_open' => false, 'is_closed' => false],
            ['key' => 'escalated',    'label' => 'Escalated',    'color' => 'danger',  'sort_order' => 30, 'is_open' => false, 'is_closed' => false],
            ['key' => 'resolved',     'label' => 'Resolved',     'color' => 'success', 'sort_order' => 40, 'is_open' => false, 'is_closed' => true],
            ['key' => 'dismissed',    'label' => 'Dismissed',    'color' => 'gray',    'sort_order' => 50, 'is_open' => false, 'is_closed' => true],
        ];

        foreach ($rows as $r) {
            IncidentStatusOption::query()->updateOrCreate(
                ['tenant_id' => null, 'key' => $r['key']],
                $r,
            );
        }
    }

    private function levels(): void
    {
        // Three default levels mirroring the typical operational hierarchy:
        // local (leaf beat), district (parent), regional (grandparent).
        // Higher level_numbers represent broader escalations.
        $rows = [
            ['level_number' => 1, 'label' => 'Local',    'color' => 'warning', 'description' => 'Device left its assigned (leaf) beat.'],
            ['level_number' => 2, 'label' => 'District', 'color' => 'danger',  'description' => 'Device left the parent beat boundary.'],
            ['level_number' => 3, 'label' => 'Regional', 'color' => 'danger',  'description' => 'Device left the grandparent beat boundary.'],
        ];

        foreach ($rows as $r) {
            IncidentLevel::query()->updateOrCreate(
                ['tenant_id' => null, 'level_number' => $r['level_number']],
                $r,
            );
        }
    }
}
