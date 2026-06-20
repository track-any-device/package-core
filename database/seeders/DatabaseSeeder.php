<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        $this->call([
            AdminSeeder::class,
            CountrySeeder::class,
            DriverSeeder::class,
            DeviceTypeSeeder::class,
            AssigneeTypeSeeder::class,
            AlertRuleSeeder::class,
            IncidentTaxonomySeeder::class,
            NavLinkSeeder::class,
            HomePageSeeder::class,
            PublicPageSeeder::class,
            IndustrySeeder::class,
            BlogSeeder::class,
            PolicyVersionSeeder::class,
            WorkflowSeeder::class,
            TenantSeeder::class,
            WarehouseSeeder::class,
        ]);
    }
}
