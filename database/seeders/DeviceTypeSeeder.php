<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Models\DeviceType;
use Illuminate\Database\Seeder;

class DeviceTypeSeeder extends Seeder
{
    public function run(): void
    {
        DeviceType::updateOrCreate(
            ['slug' => 'p901'],
            [
                'name' => 'P901',
                'driver_class' => 'App\\Drivers\\P901Driver',
                'description' => 'P901 Smart ID Card GPS Walkie-Talkie. Passive SMS-only. See docs/devices/p901.md.',
                'image' => '/devices/p901/main.jpg',
                'is_active' => true,
                'configuration_schema' => [
                    'default_password' => '123456',
                    'communication_mode' => 'passive',
                    'tracking_modes' => [0, 1, 2, 3],
                ],
            ]
        );

        DeviceType::updateOrCreate(
            ['slug' => 'gf-07'],
            [
                'name' => 'GF-07',
                'driver_class' => 'App\\Drivers\\GF07Driver',
                'description' => 'GF-07 Mini GPS Tracker. Passive SMS-only, no password. See docs/devices/gf07.md.',
                'image' => '/devices/gf_07/main.jpg',
                'is_active' => true,
                'configuration_schema' => [
                    'communication_mode' => 'passive',
                ],
            ]
        );

        DeviceType::updateOrCreate(
            ['slug' => 'aot120'],
            [
                'name' => 'AOT120',
                'driver_class' => 'App\\Drivers\\AOT120Driver',
                'stream_channel' => 'jt808',
                'default_password' => '123456',
                'description' => 'AOT120 vehicle GPS tracker. JT808 stream telemetry + SMS configuration. Engine relay, vibration / speeding / fatigue alarms. See docs/devices/aot120.md.',
                'is_active' => true,
                'configuration_schema' => [
                    'default_password' => '123456',
                    'communication_mode' => 'both',
                    'has_relay' => true,
                ],
            ]
        );

        // ── TAD101 device types ──────────────────────────────────────────────
        // All four share the Tad101Driver and connect over Soketi. Each one
        // gets its own slug so the UI can show a tailored onboarding guide
        // (Android SDK, iOS Swift Package, Arduino sketch, RPi Python).
        // Mobile app device types use JT808 TCP (react-native-tcp-socket).
        // The app auto-registers on login; IMEI is 09 + zero-padded user ID.
        // P901Driver parses standard JT808 0x0200 location frames.
        // onboarding_status is set to `verified` on creation so no SMS is sent.
        $mobileTypes = [
            [
                'slug' => 'android_app',
                'name' => 'Android App',
                'description' => 'Android mobile devices running the TAD app. JT808 TCP location streaming (react-native-tcp-socket). GPS, speed, direction, battery from device sensors.',
            ],
            [
                'slug' => 'ios_app',
                'name' => 'iOS App',
                'description' => 'iOS mobile devices running the TAD app. JT808 TCP location streaming (react-native-tcp-socket). CoreLocation GPS, speed, direction, battery.',
            ],
        ];

        foreach ($mobileTypes as $type) {
            DeviceType::updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name'         => $type['name'],
                    'driver_class' => 'App\\Drivers\\P901Driver',
                    'stream_channel' => 'jt808',
                    'description'  => $type['description'],
                    'is_active'    => true,
                    'configuration_schema' => [
                        'communication_mode' => 'stream',
                        'category'           => 'mobile',
                        'protocol'           => 'JT808',
                    ],
                ]
            );
        }

        $tad101Types = [
            [
                'slug' => 'arduino',
                'name' => 'Arduino',
                'description' => 'Arduino-family boards (Uno WiFi, MKR WiFi 1010, ESP8266, ESP32) sending telemetry via TAD101 over WebSocket. Custom IoT sensors, environment monitors, hobbyist vehicle trackers. See /docs/tad101/arduino.',
                'communication_mode' => 'stream',
                'category' => 'iot',
            ],
            [
                'slug' => 'raspberry_pi',
                'name' => 'Raspberry Pi',
                'description' => 'Raspberry Pi boards (Zero W, 3B+, 4B, 5). Full Linux environment, Python SDK, GPS HAT support, optional SIM HAT for SMS fallback. See /docs/tad101/raspberry-pi.',
                'communication_mode' => 'stream',
                'category' => 'iot',
            ],
        ];

        foreach ($tad101Types as $type) {
            DeviceType::updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'driver_class' => 'TrackAnyDevice\\Tad101\\Tad101Driver',
                    'stream_channel' => 'soketi',
                    'description' => $type['description'],
                    'is_active' => true,
                    'configuration_schema' => [
                        'protocol' => 'TAD101',
                        'protocol_version' => '1.0.0',
                        'communication_mode' => $type['communication_mode'],
                        'category' => $type['category'],
                        'channel_template' => 'tad101.device.{imei}',
                    ],
                ]
            );
        }

        // ── Store / catalog products ──────────────────────────────────────────
        // These appear on the /shop page as purchasable products.
        // No driver_class is set — these are catalogue-only entries until a
        // real driver is assigned via the admin DeviceType editor.
        $storeProducts = [
            ['slug' => 'gps-tracker-pro',     'name' => 'GPS Tracker Pro',       'description' => 'Real-time location tracking with long battery life.',                  'price_usd' => 79.00,  'price_pkr' => 22000, 'badge_label' => 'Best Seller', 'badge_color' => '#2563eb', 'is_featured' => true],
            ['slug' => 'temperature-sensor',  'name' => 'Temperature Sensor',    'description' => 'Monitor temperature in real time with precision.',                     'price_usd' => 59.00,  'price_pkr' => 16500, 'badge_label' => 'New',         'badge_color' => '#16a34a', 'is_featured' => true],
            ['slug' => 'water-level-sensor',  'name' => 'Water Level Sensor',    'description' => 'Accurate water level monitoring for tanks, rivers, and reservoirs.',  'price_usd' => 69.00,  'price_pkr' => 19500, 'badge_label' => null,          'badge_color' => null,      'is_featured' => true],
            ['slug' => 'battery-monitor',     'name' => 'Battery Monitor',       'description' => 'Track battery status and performance remotely.',                       'price_usd' => 69.00,  'price_pkr' => 19500, 'badge_label' => null,          'badge_color' => null,      'is_featured' => true],
            ['slug' => 'iot-gateway',         'name' => 'IoT Gateway',           'description' => 'Connect and manage multiple sensors and devices.',                     'price_usd' => 129.00, 'price_pkr' => 36000, 'badge_label' => null,          'badge_color' => null,      'is_featured' => true],
            ['slug' => 'asset-tag',           'name' => 'Asset Tag',             'description' => 'Compact BLE + GPS tag for easy asset tracking.',                      'price_usd' => 39.00,  'price_pkr' => 11000, 'badge_label' => null,          'badge_color' => null,      'is_featured' => true],
            ['slug' => 'solar-gps-tracker',   'name' => 'Solar GPS Tracker',     'description' => 'Solar-powered tracker for outdoor and remote assets.',                'price_usd' => 99.00,  'price_pkr' => 27500, 'badge_label' => 'Solar Powered', 'badge_color' => '#d97706', 'is_featured' => false],
            ['slug' => 'door-sensor',         'name' => 'Door Sensor',           'description' => 'Detect door open/close events in real time.',                         'price_usd' => 29.00,  'price_pkr' => 8200,  'badge_label' => null,          'badge_color' => null,      'is_featured' => false],
            ['slug' => 'fuel-level-sensor',   'name' => 'Fuel Level Sensor',     'description' => 'Monitor fuel levels and prevent losses.',                             'price_usd' => 89.00,  'price_pkr' => 25000, 'badge_label' => null,          'badge_color' => null,      'is_featured' => false],
            ['slug' => 'humidity-sensor',     'name' => 'Humidity Sensor',       'description' => 'Track humidity levels for better environment control.',               'price_usd' => 59.00,  'price_pkr' => 16500, 'badge_label' => null,          'badge_color' => null,      'is_featured' => false],
            ['slug' => 'rugged-asset-tracker', 'name' => 'Rugged Asset Tracker',  'description' => 'IP67 waterproof tracker built for harsh field environments.',         'price_usd' => 109.00, 'price_pkr' => 30500, 'badge_label' => 'IP67',        'badge_color' => '#475569', 'is_featured' => false],
            ['slug' => 'motion-sensor',       'name' => 'Motion Sensor',         'description' => 'Detect movement and receive instant alerts.',                         'price_usd' => 39.00,  'price_pkr' => 11000, 'badge_label' => null,          'badge_color' => null,      'is_featured' => false],
        ];

        foreach ($storeProducts as $product) {
            DeviceType::updateOrCreate(
                ['slug' => $product['slug']],
                [
                    'name' => $product['name'],
                    'driver_class' => null,
                    'description' => $product['description'],
                    'is_active' => true,
                    'is_featured' => $product['is_featured'],
                    'price_usd' => $product['price_usd'],
                    'price_pkr' => $product['price_pkr'],
                    'badge_label' => $product['badge_label'],
                    'badge_color' => $product['badge_color'],
                    'quantity' => 100,
                    'min_quantity' => 1,
                    'quantity_multiple' => 1,
                    'configuration_schema' => ['communication_mode' => 'passive', 'store_only' => true],
                ]
            );
        }
    }
}
