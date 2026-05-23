<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use App\Drivers\Jt808Driver;
use App\Drivers\P901Driver;
use TrackAnyDevice\Core\Models\ChargingSet;
use TrackAnyDevice\Core\Models\Chip;
use TrackAnyDevice\Core\Models\ComputeBoard;
use TrackAnyDevice\Core\Models\ConnectingCable;
use TrackAnyDevice\Core\Models\DeviceType;
use TrackAnyDevice\Core\Models\Driver;
use TrackAnyDevice\Core\Models\Sensor;
use Illuminate\Database\Seeder;

/**
 * Builds the full P901 catalogue: chips, board, cable, charger, sensors,
 * and the device-type ⇄ accessory pivot links.
 */
class P901CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $lteChip = Chip::updateOrCreate(
            ['name' => 'LTE Cat-1 Module', 'manufacturer' => 'Cantrack'],
            [
                'type' => 'cellular',
                'specifications' => [
                    'bands_fdd' => ['B1', 'B3', 'B5', 'B8'],
                    'bands_tdd' => ['B34', 'B38', 'B39', 'B40', 'B41'],
                ],
            ]
        );

        $gnssChip = Chip::updateOrCreate(
            ['name' => 'GNSS Module', 'manufacturer' => 'Cantrack'],
            ['type' => 'gnss']
        );

        $board = ComputeBoard::updateOrCreate(
            ['name' => 'P901 Integrated Board', 'manufacturer' => 'Cantrack'],
            ['notes' => 'Avg current <110mA, Standby <3mA intercom-off / <10mA intercom-on'],
        );

        $cable = ConnectingCable::updateOrCreate(
            ['name' => 'P901 Micro-USB Cable'],
            [
                'connector_type' => 'Micro-USB',
                'protocol' => 'usb',
                'baud_rates' => [9600, 115200],
            ]
        );

        $charger = ChargingSet::updateOrCreate(
            ['name' => 'P901 USB Charger'],
            [
                'connector' => 'Micro-USB',
                'voltage' => 5.0,
                'current_ma' => 1000,
                'wireless' => false,
                'notes' => 'Full charge ~4h. Device auto-powers-on when cable connected.',
            ]
        );

        $driver = Driver::where('class', Jt808Driver::class)->first();

        $p901 = DeviceType::updateOrCreate(
            ['slug' => 'p901'],
            [
                'name' => 'P901',
                'driver_id' => $driver?->id,
                'driver_class' => P901Driver::class,
                'stream_channel' => 'jt808',
                'default_password' => '123456',
                'description' => 'Cantrack P901 Smart ID Card GPS Walkie-Talkie. LTE Cat-1, 2000mAh, PTT intercom, GPS + LBS tracking.',
                'meta' => [
                    'default_mode' => 3,
                    'default_mode_timeout' => '30S',
                ],
            ]
        );

        $sensorSlugs = ['gps', 'altitude', 'speed', 'direction', 'battery_percent', 'gsm_signal', 'satellites', 'punch_in', 'punch_out', 'sos', 'intercom'];
        $sensorIds = Sensor::whereIn('slug', $sensorSlugs)->pluck('id');

        $p901->sensors()->syncWithoutDetaching($sensorIds);
        $p901->chips()->syncWithoutDetaching([$lteChip->id, $gnssChip->id]);
        $p901->computeBoards()->syncWithoutDetaching([$board->id]);
        $p901->connectingCables()->syncWithoutDetaching([$cable->id]);
        $p901->chargingSets()->syncWithoutDetaching([$charger->id]);

        $lteChip->sensors()->syncWithoutDetaching(
            Sensor::whereIn('slug', ['gsm_signal', 'battery_percent'])->pluck('id')->all()
        );
        $gnssChip->sensors()->syncWithoutDetaching(
            Sensor::whereIn('slug', ['gps', 'altitude', 'speed', 'direction', 'satellites'])->pluck('id')->all()
        );
    }
}
