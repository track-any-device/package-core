<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Models\DeviceType;
use TrackAnyDevice\Core\Models\Sensor;
use Illuminate\Database\Seeder;

class SensorSeeder extends Seeder
{
    public function run(): void
    {
        $sensors = [
            ['slug' => 'gps', 'name' => 'GPS Location', 'label' => 'GPS Location', 'data_type' => 'coordinate', 'unit' => null, 'sort_order' => 1, 'description' => 'Latitude/longitude positioning via GNSS'],
            ['slug' => 'altitude', 'name' => 'Altitude', 'label' => 'Altitude', 'data_type' => 'integer', 'unit' => 'm', 'sort_order' => 2, 'description' => 'Altitude above sea level'],
            ['slug' => 'speed', 'name' => 'Speed', 'label' => 'Speed', 'data_type' => 'float', 'unit' => 'km/h', 'sort_order' => 3, 'description' => 'Ground speed from GNSS'],
            ['slug' => 'direction', 'name' => 'Direction', 'label' => 'Direction', 'data_type' => 'integer', 'unit' => '°', 'sort_order' => 4, 'description' => 'Heading, 0=North'],
            ['slug' => 'battery_percent', 'name' => 'Battery', 'label' => 'Battery', 'data_type' => 'integer', 'unit' => '%', 'sort_order' => 5, 'description' => 'Battery charge level'],
            ['slug' => 'battery_voltage', 'name' => 'Battery Voltage', 'label' => 'Battery Voltage', 'data_type' => 'integer', 'unit' => 'mV', 'sort_order' => 6, 'description' => 'Battery voltage'],
            ['slug' => 'battery_capacity', 'name' => 'Battery Capacity', 'label' => 'Battery Capacity', 'data_type' => 'integer', 'unit' => 'mAh', 'sort_order' => 7, 'description' => 'Design capacity'],
            ['slug' => 'gsm_signal', 'name' => 'GSM Signal', 'label' => 'GSM Signal', 'data_type' => 'integer', 'unit' => 'dBm', 'protocol' => '2G/3G/4G/5G', 'sort_order' => 8, 'description' => 'Cellular signal strength'],
            ['slug' => 'satellites', 'name' => 'Satellites', 'label' => 'Satellites', 'data_type' => 'integer', 'unit' => null, 'sort_order' => 9, 'description' => 'GNSS satellites in view'],
            ['slug' => 'temperature', 'name' => 'Temperature', 'label' => 'Temperature', 'data_type' => 'float', 'unit' => '°C', 'sort_order' => 10, 'description' => 'Ambient or cargo temperature'],
            ['slug' => 'level', 'name' => 'Level', 'label' => 'Level', 'data_type' => 'float', 'unit' => null, 'sort_order' => 11, 'description' => 'Tank or material level'],
            ['slug' => 'vibration', 'name' => 'Vibration', 'label' => 'Vibration', 'data_type' => 'boolean', 'unit' => null, 'sort_order' => 12, 'description' => 'Vibration / movement detection'],
            ['slug' => 'sos', 'name' => 'SOS', 'label' => 'SOS', 'data_type' => 'boolean', 'unit' => null, 'sort_order' => 13, 'description' => 'SOS button pressed'],
            ['slug' => 'punch_in', 'name' => 'Punch In', 'label' => 'Punch In', 'data_type' => 'boolean', 'unit' => null, 'sort_order' => 14, 'description' => 'On-duty punch-in'],
            ['slug' => 'punch_out', 'name' => 'Punch Out', 'label' => 'Punch Out', 'data_type' => 'boolean', 'unit' => null, 'sort_order' => 15, 'description' => 'Off-duty punch-out'],
            ['slug' => 'intercom', 'name' => 'Intercom', 'label' => 'Intercom', 'data_type' => 'boolean', 'unit' => null, 'sort_order' => 16, 'description' => 'Push-to-talk intercom event'],

            // ── TAD101 additions (DOC-2) ─────────────────────────────────
            ['slug' => 'hdop', 'name' => 'HDOP', 'label' => 'HDOP', 'data_type' => 'float', 'unit' => null, 'sort_order' => 17, 'description' => 'Horizontal dilution of precision'],
            ['slug' => 'positioning_type', 'name' => 'Positioning Type', 'label' => 'Positioning Type', 'data_type' => 'string', 'unit' => null, 'sort_order' => 18, 'description' => 'Position source (gps, lbs, wifi, fused)'],
            ['slug' => 'battery_eta', 'name' => 'Battery ETA', 'label' => 'Battery ETA', 'data_type' => 'string', 'unit' => null, 'sort_order' => 19, 'description' => 'Estimated time until empty (human-readable, e.g. 6h 22m)'],
            ['slug' => 'network_signal', 'name' => 'Network Signal', 'label' => 'Network Signal', 'data_type' => 'integer', 'unit' => 'bars', 'sort_order' => 20, 'description' => 'Normalised cellular signal bars (0–5)'],
            ['slug' => 'mcc', 'name' => 'MCC', 'label' => 'Mobile Country Code', 'data_type' => 'integer', 'unit' => null, 'sort_order' => 21, 'description' => 'Mobile country code of serving cell'],
            ['slug' => 'mnc', 'name' => 'MNC', 'label' => 'Mobile Network Code', 'data_type' => 'integer', 'unit' => null, 'sort_order' => 22, 'description' => 'Mobile network code of serving cell'],
            ['slug' => 'lac', 'name' => 'LAC', 'label' => 'Location Area Code', 'data_type' => 'integer', 'unit' => null, 'sort_order' => 23, 'description' => 'Cellular location area code'],
            ['slug' => 'cell_id', 'name' => 'Cell ID', 'label' => 'Cell ID', 'data_type' => 'integer', 'unit' => null, 'sort_order' => 24, 'description' => 'Cellular cell identifier'],
            ['slug' => 'custom_type', 'name' => 'Custom Event Type', 'label' => 'Custom Event Type', 'data_type' => 'string', 'unit' => null, 'sort_order' => 25, 'description' => 'Device-defined custom event discriminator'],
        ];

        foreach ($sensors as $data) {
            Sensor::updateOrCreate(['slug' => $data['slug']], $data);
        }

        // Attach common sensors to seeded device types where present.
        $defaultByType = [
            'p901' => ['gps', 'altitude', 'speed', 'direction', 'battery_percent', 'gsm_signal', 'satellites', 'punch_in', 'punch_out', 'sos', 'intercom'],
            'gf-07' => ['gps', 'battery_percent', 'gsm_signal'],
            'jt808' => ['gps', 'altitude', 'speed', 'direction', 'battery_percent', 'gsm_signal', 'satellites', 'temperature'],
            // TAD101 device types — every sensor the protocol can carry, so
            // the admin sees the full surface and can disable per device.
            'android_app' => ['gps', 'altitude', 'speed', 'direction', 'hdop', 'positioning_type', 'battery_percent', 'battery_voltage', 'gsm_signal', 'network_signal', 'satellites', 'mcc', 'mnc', 'lac', 'cell_id', 'punch_in', 'punch_out', 'sos'],
            'ios_app' => ['gps', 'altitude', 'speed', 'direction', 'hdop', 'positioning_type', 'battery_percent', 'gsm_signal', 'network_signal', 'satellites', 'punch_in', 'punch_out', 'sos'],
            'arduino' => ['gps', 'altitude', 'speed', 'direction', 'satellites', 'battery_percent', 'battery_voltage', 'temperature', 'level', 'sos', 'custom_type'],
            'raspberry_pi' => ['gps', 'altitude', 'speed', 'direction', 'satellites', 'hdop', 'battery_percent', 'battery_voltage', 'temperature', 'level', 'sos', 'custom_type'],
        ];

        foreach ($defaultByType as $slug => $sensorSlugs) {
            $type = DeviceType::where('slug', $slug)->first();
            if ($type === null) {
                continue;
            }
            $ids = Sensor::whereIn('slug', $sensorSlugs)->pluck('id');
            $type->sensors()->syncWithoutDetaching($ids);
        }
    }
}
