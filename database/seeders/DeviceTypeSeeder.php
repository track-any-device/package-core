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

        // ── GT06 protocol device types ────────────────────────────────────────
        // Devices that connect over the Concox/GT06 binary TCP protocol to
        // server-gt06 (TCP :7019). Telemetry is published to the gt06:telemetry
        // Redis Stream and consumed by package-gt06's `gt06:consume` command.
        // driver_class is null until package-gt06 is released and assigned.
        // DB_DEVICE_TYPE_ID=2 in server-gt06 auto-registers unknown devices
        // under the 'concox-gt06n' type by convention (most common GT06 family).
        $gt06Types = [
            [
                'slug'        => 'concox-gt06n',
                'name'        => 'Concox GT06N',
                'description' => 'Concox GT06N vehicle GPS tracker. GT06 binary TCP protocol (server-gt06 :7019). SOS button, built-in microphone, ACC detection, engine relay output. Common for motorcycles and light vehicles.',
                'has_sos'     => true,
                'has_relay'   => true,
            ],
            [
                'slug'        => 'concox-gt06d',
                'name'        => 'Concox GT06D',
                'description' => 'Concox GT06D OBD-II vehicle tracker. GT06 binary TCP protocol (server-gt06 :7019). Reads OBD-II diagnostics, fuel data, engine fault codes. Plug-and-play car installation.',
                'has_sos'     => false,
                'has_relay'   => false,
            ],
            [
                'slug'        => 'concox-wetrack2',
                'name'        => 'Concox WeTrack2',
                'description' => 'Concox WeTrack2 hardwired vehicle tracker. GT06 binary TCP protocol (server-gt06 :7019). Engine relay, ACC detection, power-cut alarm, 3-axis accelerometer, IP67 waterproof.',
                'has_sos'     => false,
                'has_relay'   => true,
            ],
            [
                'slug'        => 'mictrack-mt600',
                'name'        => 'Mictrack MT600',
                'description' => 'Mictrack MT600 portable fleet GPS tracker. GT06 binary TCP protocol (server-gt06 :7019). Long battery life, IP67 waterproof, motion sensor, magnetic mount. Popular for asset and fleet tracking.',
                'has_sos'     => true,
                'has_relay'   => false,
            ],
        ];

        foreach ($gt06Types as $type) {
            DeviceType::updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name'                 => $type['name'],
                    'driver_class'         => null,
                    'stream_channel'       => 'gt06',
                    'description'          => $type['description'],
                    'is_active'            => true,
                    'configuration_schema' => [
                        'communication_mode' => 'stream',
                        'protocol'           => 'GT06',
                        'server'             => 'server-gt06',
                        'tcp_port'           => 7019,
                        'stream_key'         => 'gt06:telemetry',
                        'has_sos'            => $type['has_sos'],
                        'has_relay'          => $type['has_relay'],
                        'heartbeat_interval' => 60,
                    ],
                ]
            );
        }

        // ── H02 protocol device types ─────────────────────────────────────────
        // Devices that connect to server-h02-tcp (TCP :7020) or
        // server-h02-udp (UDP :7021). Both transports publish to h02:telemetry
        // with a transport=tcp|udp field. Consumed by package-h02's `h02:consume`.
        // driver_class is null until package-h02 is released and assigned.
        // DB_DEVICE_TYPE_ID=3 in server-h02 auto-registers unknown devices.
        $h02Types = [
            [
                'slug'        => 'sinotrack-st901',
                'name'        => 'Sinotrack ST-901',
                'description' => 'Sinotrack ST-901 waterproof portable tracker. H02 text TCP/UDP protocol (server-h02 :7020/:7021). Built-in GSM antenna, 400 mAh backup battery, SOS button. Popular for motorcycles and covert vehicle tracking.',
                'has_sos'     => true,
            ],
            [
                'slug'        => 'sinotrack-st902',
                'name'        => 'Sinotrack ST-902',
                'description' => 'Sinotrack ST-902 hardwired vehicle tracker. H02 text TCP/UDP protocol (server-h02 :7020/:7021). ACC detection, engine relay, vibration alarm, power-cut alert. Standard car installation.',
                'has_sos'     => true,
            ],
            [
                'slug'        => 'sinotrack-st902w',
                'name'        => 'Sinotrack ST-902W',
                'description' => 'Sinotrack ST-902W vehicle tracker with WiFi positioning. H02 text TCP/UDP protocol (server-h02 :7020/:7021). GPS + WiFi hotspot scanning for improved urban and indoor location accuracy.',
                'has_sos'     => false,
            ],
        ];

        foreach ($h02Types as $type) {
            DeviceType::updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name'                 => $type['name'],
                    'driver_class'         => null,
                    'stream_channel'       => 'h02',
                    'description'          => $type['description'],
                    'is_active'            => true,
                    'configuration_schema' => [
                        'communication_mode' => 'stream',
                        'protocol'           => 'H02',
                        'server'             => 'server-h02',
                        'tcp_port'           => 7020,
                        'udp_port'           => 7021,
                        'stream_key'         => 'h02:telemetry',
                        'has_sos'            => $type['has_sos'],
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
