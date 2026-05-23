<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Models\Domain;
use TrackAnyDevice\Core\Models\Tenant;
use TrackAnyDevice\Core\Models\TenantStatus;
use Illuminate\Database\Seeder;

/**
 * Creates the "Suthra Punjab" tenant (Environment Protection & Climate Change Department).
 *
 * Run once after deploying to the dev server:
 *   php artisan db:seed --class=SuthraPunjabTenantSeeder
 *
 * Assumes APP_DOMAIN=dev-fleet-tracking.code-fellow.com so the portal
 * will be accessible at https://suthra-punjab.dev-fleet-tracking.code-fellow.com/
 */
class SuthraPunjabTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::updateOrCreate(
            ['slug' => 'suthra-punjab'],
            [
                'name' => 'Suthra Punjab',
                'app_name' => 'صاف پنجاب Fleet Monitoring',
                'type' => 'portal',
                'status' => TenantStatus::Approved,
                'approved_at' => now(),
                'metadata' => [
                    'department' => 'Environment Protection & Climate Change Department',
                    'government' => 'Government of Punjab',
                ],
            ]
        );

        $appDomain = env('APP_DOMAIN', 'dev-fleet-tracking.code-fellow.com');
        $hostname = "suthra-punjab.{$appDomain}";

        Domain::firstOrCreate(
            ['domain' => $hostname],
            ['tenant_id' => $tenant->id, 'is_primary' => true]
        );

        $this->command->info("Tenant: {$tenant->name} (id: {$tenant->id})");
        $this->command->line("  Domain: https://{$hostname}/");
    }
}
