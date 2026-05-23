<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use App\Drivers\AOT120Driver;
use App\Drivers\GF07Driver;
use App\Drivers\Jt808Driver;
use App\Drivers\P901Driver;
use TrackAnyDevice\Tad101\Tad101Driver;
use TrackAnyDevice\Core\Models\Driver;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    public function run(): void
    {
        $drivers = [
            [
                'name' => 'JT808Driver',
                'class' => Jt808Driver::class,
                'stream_channel' => 'jt808',
                'supports_gsm_commands' => true,
                'supports_stream' => true,
                'version' => '1.3',
                'notes' => 'JT/T 808-2019 protocol over TCP. Handles P901, P801, VT01.',
            ],
            [
                'name' => 'P901Driver',
                'class' => P901Driver::class,
                'stream_channel' => 'jt808',
                'supports_gsm_commands' => true,
                'supports_stream' => true,
                'version' => '1.0',
                'notes' => 'Cantrack P901 — stream + SMS commands.',
            ],
            [
                'name' => 'GF07Driver',
                'class' => GF07Driver::class,
                'stream_channel' => 'none',
                'supports_gsm_commands' => true,
                'supports_stream' => false,
                'version' => '1.0',
                'notes' => 'GF-07 Mini GPS Tracker — SMS-only.',
            ],
            [
                'name' => 'AOT120Driver',
                'class' => AOT120Driver::class,
                'stream_channel' => 'jt808',
                'supports_gsm_commands' => true,
                'supports_stream' => true,
                'version' => '1.0',
                'notes' => 'AOT120 vehicle tracker — JT808 stream + SMS config.',
            ],
            [
                'name' => 'TAD101Driver',
                'class' => Tad101Driver::class,
                'stream_channel' => 'soketi',
                'supports_gsm_commands' => true,
                'supports_stream' => true,
                'version' => '1.0.0',
                'notes' => 'TAD101 universal WebSocket protocol over Soketi. Powers Android/iOS apps, Arduino/ESP32 boards, Raspberry Pi clusters, and any Pusher-compatible client. Strict superset of every other driver\'s command surface — see docs/devices/tad101.md.',
            ],
        ];

        foreach ($drivers as $row) {
            Driver::updateOrCreate(['class' => $row['class']], $row);
        }
    }
}
