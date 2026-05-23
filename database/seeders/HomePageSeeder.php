<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Models\Page;
use TrackAnyDevice\Core\Models\PageSection;
use TrackAnyDevice\Core\Models\Solution;
use TrackAnyDevice\Core\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Seeds the 'home' CMS page using the new 12-component page-builder schema.
 *
 * Re-running this seeder is safe — every section is matched on
 * (sectionable, identifier) so existing rows are updated in place.
 *
 *   php artisan db:seed --class=HomePageSeeder
 */
class HomePageSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Solutions ──────────────────────────────────────────────────────
        $solutionData = [
            ['title' => 'Fleet Management',  'slug' => 'fleet-management',  'description' => 'Track vehicles in real time, optimise routes, and reduce fuel costs.',         'icon_name' => 'Truck',       'gradient_from' => 'blue-900',   'gradient_to' => 'blue-700',   'sort_order' => 1],
            ['title' => 'Asset Tracking',    'slug' => 'asset-tracking',    'description' => 'Know where every asset is at all times — no more lost equipment.',            'icon_name' => 'Package',     'gradient_from' => 'slate-800',  'gradient_to' => 'slate-600',  'sort_order' => 2],
            ['title' => 'Agriculture',       'slug' => 'agriculture',       'description' => 'Monitor soil moisture, temperature, and irrigation across your fields.',       'icon_name' => 'Leaf',        'gradient_from' => 'green-900',  'gradient_to' => 'green-700',  'sort_order' => 3],
            ['title' => 'Water Management',  'slug' => 'water-management',  'description' => 'Real-time water level sensors for tanks, rivers, and reservoirs.',            'icon_name' => 'Droplets',    'gradient_from' => 'cyan-900',   'gradient_to' => 'cyan-700',   'sort_order' => 4],
            ['title' => 'Cold Chain',        'slug' => 'cold-chain',        'description' => 'Ensure product integrity from warehouse to last-mile delivery.',              'icon_name' => 'Thermometer', 'gradient_from' => 'indigo-900', 'gradient_to' => 'indigo-700', 'sort_order' => 5],
            ['title' => 'Smart City & IoT',  'slug' => 'smart-city-iot',    'description' => 'Connected infrastructure monitoring for modern urban environments.',          'icon_name' => 'Building2',   'gradient_from' => 'purple-900', 'gradient_to' => 'purple-700', 'sort_order' => 6],
        ];

        foreach ($solutionData as $s) {
            Solution::updateOrCreate(['slug' => $s['slug']], $s + ['is_active' => true, 'is_featured' => true]);
        }

        // ── 2. Testimonials ───────────────────────────────────────────────────
        $testimonialData = [
            ['name' => 'Michael R.', 'role' => 'Fleet Manager',      'quote' => 'Track Any Device has completely transformed our fleet operations. The real-time tracking and alerts are reliable and accurate.',     'avatar_initials' => 'MR', 'avatar_color' => '#2563eb', 'rating' => 5, 'sort_order' => 1],
            ['name' => 'Sarah K.',   'role' => 'Operations Director', 'quote' => 'The sensors are top quality and the platform is very easy to use. It helps us monitor everything from temperature to water levels.', 'avatar_initials' => 'SK', 'avatar_color' => '#16a34a', 'rating' => 5, 'sort_order' => 2],
            ['name' => 'David L.',   'role' => 'IT Manager',          'quote' => 'Excellent devices, great battery life, and outstanding support team. Highly recommended for any field operation.',                 'avatar_initials' => 'DL', 'avatar_color' => '#7c3aed', 'rating' => 5, 'sort_order' => 3],
        ];

        foreach ($testimonialData as $t) {
            Testimonial::updateOrCreate(
                ['name' => $t['name'], 'role' => $t['role']],
                $t + ['is_featured' => true, 'is_approved' => true]
            );
        }

        // ── 3. Home page ──────────────────────────────────────────────────────
        $page = Page::updateOrCreate(
            ['slug' => 'home'],
            ['title' => 'Home', 'meta_title' => 'Track Any Device — IoT platform for every device', 'is_active' => true]
        );

        // ── 4. Sections (12-component schema) ─────────────────────────────────
        $sort = 0;

        $this->upsert($page, 'hero', 'home-hero', ++$sort, [
            'size' => 'full',
            'alignment' => 'center',
            'eyebrow' => 'IoT platform',
            'title' => 'Track Any Device, Anywhere',
            'title_highlight' => 'Anywhere',
            'subtitle' => 'Smart tracking for GPS, temperature, water level, battery, assets, vehicles, and IoT sensors.',
            'body' => 'Monitor your devices in real time with a reliable platform built for modern businesses, field operations, and connected infrastructure.',
            'bg' => [
                'kind' => 'map',
                'map_center_lat' => 31.5204,
                'map_center_lng' => 74.3587,
                'map_zoom' => 6,
                'map_show_devices' => true,
                'overlay_alpha' => 35,
            ],
            'buttons' => [
                ['label' => 'Shop Devices', 'link' => '/products', 'icon' => 'ShoppingCart', 'variant' => 'primary'],
                ['label' => 'Explore More', 'link' => '#explore-more', 'icon' => 'ArrowDown', 'variant' => 'outline'],
            ],
        ]);

        $this->upsert($page, 'cards_grid', 'why-tad', ++$sort, [
            'eyebrow' => 'Why Track Any Device',
            'title' => 'Everything you need to monitor your fleet',
            'subtitle' => 'Built for government and enterprise field operations.',
            'columns' => 3,
            'card_style' => 'icon',
            'items' => [
                ['icon' => 'MapPin',  'title' => 'Real-Time Tracking',    'description' => 'Live GPS positions with sub-second updates and interactive map views.'],
                ['icon' => 'Cpu',     'title' => 'Multi-Sensor Support',  'description' => 'GPS, temperature, water level, battery, vibration — one platform.'],
                ['icon' => 'Battery', 'title' => 'Long Battery Life',     'description' => 'Energy-efficient hardware designed for months of field operation.'],
                ['icon' => 'Radio',   'title' => 'Reliable Connectivity', 'description' => '4G LTE, NB-IoT, LoRa, and WiFi options for any environment.'],
                ['icon' => 'Cloud',   'title' => 'Cloud Platform',        'description' => 'Scalable infrastructure with 99.9% uptime and global PoPs.'],
                ['icon' => 'Bell',    'title' => 'Instant Alerts',        'description' => 'SMS, email, and push notifications the moment thresholds are crossed.'],
            ],
        ]);

        $this->upsert($page, 'featured_solutions_grid', 'explore-more', ++$sort, [
            'eyebrow' => 'Solutions',
            'title' => 'Trusted Tracking Solutions',
            'subtitle' => 'From small devices to large assets — solutions for every operation.',
            'columns' => 3,
            'max_items' => 6,
            'show_buttons_on_cards' => true,
            'card_button_label' => 'Learn more',
            'buttons' => [
                ['label' => 'Explore All Solutions', 'link' => '/solutions', 'icon' => 'ArrowRight', 'variant' => 'primary'],
            ],
        ]);

        $this->upsert($page, 'featured_products_grid', 'featured-devices', ++$sort, [
            'eyebrow' => 'Hardware',
            'title' => 'Powerful Devices for Every Need',
            'subtitle' => 'Reliable. Durable. Connected.',
            'columns' => 3,
            'max_items' => 6,
            'show_buttons_on_cards' => true,
            'card_button_label' => 'View product',
            'buttons' => [
                ['label' => 'Browse Catalog', 'link' => '/products', 'icon' => 'ArrowRight', 'variant' => 'primary'],
            ],
        ]);

        $this->upsert($page, 'cards_grid', 'by-the-numbers', ++$sort, [
            'title' => 'By the numbers',
            'subtitle' => 'Built and trusted at scale.',
            'columns' => 4,
            'card_style' => 'stat',
            'items' => [
                ['icon' => 'Star',     'title' => 'Happy Customers',   'value' => '10,000+'],
                ['icon' => 'MapPin',   'title' => 'Countries Served',  'value' => '50+'],
                ['icon' => 'Cpu',      'title' => 'Devices Connected', 'value' => '100,000+'],
                ['icon' => 'Activity', 'title' => 'Platform Uptime',   'value' => '99.9%'],
            ],
        ]);

        $this->upsert($page, 'banner_5050', 'engineer-banner', ++$sort, [
            'image' => '/hero.png',
            'image_position' => 'right',
            'eyebrow' => 'For builders',
            'title' => 'Built for engineers, ready for production',
            'body' => 'Use our TAD101 universal protocol to bring any custom Arduino, Raspberry Pi, or mobile build into the same dashboard as off-the-shelf trackers.',
            'bullets' => [
                ['icon' => 'Code',     'text' => 'SDK samples for Arduino, ESP32, RPi, Android, iOS'],
                ['icon' => 'Zap',      'text' => 'Sub-second real-time updates via WebSocket'],
                ['icon' => 'Database', 'text' => 'Uniform signal schema for downstream tools'],
            ],
            'buttons' => [
                ['label' => 'Read the docs', 'link' => '/docs/tad101', 'icon' => 'BookOpen', 'variant' => 'primary'],
                ['label' => 'Shop boards', 'link' => '/products?category=compute', 'icon' => 'Cpu', 'variant' => 'outline'],
            ],
        ]);

        $this->upsert($page, 'featured_blog_slider', 'from-the-blog', ++$sort, [
            'eyebrow' => 'Insights',
            'title' => 'From the blog',
            'subtitle' => 'Engineering notes, customer stories, and platform updates.',
            'featured_blog_slug' => null,
            'list_count' => 5,
            'buttons' => [
                ['label' => 'All articles', 'link' => '/blog', 'icon' => 'ArrowRight', 'variant' => 'outline'],
            ],
        ]);

        $this->upsert($page, 'cta_section', 'final-cta', ++$sort, [
            'eyebrow' => 'Get started',
            'title' => 'Ready to start tracking?',
            'subtitle' => 'Talk to our team about your hardware, dashboards, and integrations.',
            'alignment' => 'center',
            'bg' => [
                'kind' => 'gradient',
                'gradient_from' => '#2e7d32',
                'gradient_to' => '#1a3d2b',
                'gradient_direction' => 'to-br',
            ],
            'buttons' => [
                ['label' => 'Contact Sales',  'link' => '/contact', 'icon' => 'Mail', 'variant' => 'primary'],
                ['label' => 'Browse Products', 'link' => '/products', 'icon' => 'ShoppingCart', 'variant' => 'outline'],
            ],
        ]);

        $this->command?->info("Home page seeded ({$page->allSections()->count()} sections).");
    }

    protected function upsert(Model $parent, string $type, string $identifier, int $sortOrder, array $content): void
    {
        PageSection::updateOrCreate(
            [
                'sectionable_type' => $parent->getMorphClass(),
                'sectionable_id' => $parent->getKey(),
                'identifier' => $identifier,
            ],
            [
                'type' => $type,
                'content' => $content,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]
        );
    }
}
