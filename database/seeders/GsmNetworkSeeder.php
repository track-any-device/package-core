<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Models\Country;
use TrackAnyDevice\Core\Models\GsmNetwork;
use Illuminate\Database\Seeder;

class GsmNetworkSeeder extends Seeder
{
    public function run(): void
    {
        $pakistan = Country::where('iso_code', 'PK')->first();

        $networks = [
            ['name' => 'Jazz', 'country_code' => 'PAK', 'apn' => 'jazz.net.pk'],
            ['name' => 'Zong', 'country_code' => 'PAK', 'apn' => 'zonginternet'],
            ['name' => 'Telenor', 'country_code' => 'PAK', 'apn' => 'internet'],
            ['name' => 'Ufone', 'country_code' => 'PAK', 'apn' => 'ufone.internet'],
            ['name' => 'SCO', 'country_code' => 'PAK', 'apn' => 'sco.internet'],
        ];

        foreach ($networks as $row) {
            GsmNetwork::updateOrCreate(
                ['name' => $row['name'], 'country_code' => $row['country_code']],
                array_merge($row, [
                    'is_active' => true,
                    'country_id' => $pakistan?->id,
                ]),
            );
        }
    }
}
