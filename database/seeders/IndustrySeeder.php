<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Models\Industry;
use TrackAnyDevice\Core\Models\PageSection;
use Illuminate\Database\Seeder;

/**
 * Seeds Industry records with their composed sections. Industries are the
 * vertical-market pages we target: Government, Enterprise, SMB, Consumer,
 * plus more specific verticals (Logistics, Agriculture, Smart City).
 */
class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        $industries = [
            [
                'title' => 'Government',
                'slug' => 'government',
                'description' => 'Compliance-first deployments for ministries, departments, and municipal authorities.',
                'icon_name' => 'Landmark',
                'color' => '#1565c0',
                'sort_order' => 1,
                'is_featured' => true,
                'body' => 'Bilingual portals, hosted in-country options, audit trails, and SMS-first device support. Reference deployments include the Environment Protection &amp; Climate Change Department, Government of Punjab.',
            ],
            [
                'title' => 'Logistics & Fleet',
                'slug' => 'logistics-fleet',
                'description' => 'Real-time visibility for trucking, courier, and last-mile fleets.',
                'icon_name' => 'Truck',
                'color' => '#f57c00',
                'sort_order' => 2,
                'is_featured' => true,
                'body' => 'Vehicle-grade trackers, route playback, geofence violations, driver behaviour scoring, and dispatcher-friendly dashboards.',
            ],
            [
                'title' => 'Agriculture',
                'slug' => 'agriculture',
                'description' => 'Soil moisture, irrigation, livestock, and farm-equipment telemetry.',
                'icon_name' => 'Wheat',
                'color' => '#2e7d32',
                'sort_order' => 3,
                'is_featured' => true,
                'body' => 'Battery-efficient sensor nodes that survive a season in the field plus a dashboard farmers actually use.',
            ],
            [
                'title' => 'Smart City',
                'slug' => 'smart-city',
                'description' => 'Connected infrastructure for urban operations teams.',
                'icon_name' => 'Building2',
                'color' => '#7c3aed',
                'sort_order' => 4,
                'is_featured' => true,
                'body' => 'Streetlight monitors, waste-bin levels, water-quality stations — one platform for the whole operations stack.',
            ],
            [
                'title' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Asset and people tracking for large organisations.',
                'icon_name' => 'Building2',
                'color' => '#475569',
                'sort_order' => 5,
                'is_featured' => false,
                'body' => 'White-label tenant portals, SSO, role-based access, and an API the enterprise data team can integrate.',
            ],
            [
                'title' => 'Hardware Engineering',
                'slug' => 'hardware-engineering',
                'description' => 'For teams building their own connected devices.',
                'icon_name' => 'Cpu',
                'color' => '#0891b2',
                'sort_order' => 6,
                'is_featured' => true,
                'body' => 'Buy chips, boards, sensors, and cables. Build with the TAD101 SDK. Ship to the same dashboard as our off-the-shelf trackers.',
            ],
        ];

        foreach ($industries as $i) {
            $industry = Industry::updateOrCreate(
                ['slug' => $i['slug']],
                [
                    'title' => $i['title'],
                    'description' => $i['description'],
                    'icon_name' => $i['icon_name'],
                    'color' => $i['color'],
                    'sort_order' => $i['sort_order'],
                    'is_active' => true,
                    'is_featured' => $i['is_featured'],
                ]
            );

            // Section 1: hero
            PageSection::updateOrCreate(
                ['sectionable_type' => $industry->getMorphClass(), 'sectionable_id' => $industry->id, 'identifier' => 'industry-hero'],
                [
                    'type' => 'hero',
                    'content' => [
                        'size' => 'half',
                        'alignment' => 'center',
                        'eyebrow' => 'Industry',
                        'title' => $i['title'],
                        'subtitle' => $i['description'],
                        'bg' => ['kind' => 'gradient', 'gradient_from' => $i['color'], 'gradient_to' => '#0f172a', 'gradient_direction' => 'to-br'],
                        'buttons' => [
                            ['label' => 'Talk to us', 'link' => '/contact?industry='.$i['slug'], 'icon' => 'Mail', 'variant' => 'primary'],
                            ['label' => 'Browse products', 'link' => '/products', 'icon' => 'ShoppingCart', 'variant' => 'outline'],
                        ],
                    ],
                    'sort_order' => 1,
                    'is_active' => true,
                ]
            );

            // Section 2: text overview
            PageSection::updateOrCreate(
                ['sectionable_type' => $industry->getMorphClass(), 'sectionable_id' => $industry->id, 'identifier' => 'industry-overview'],
                [
                    'type' => 'text_section',
                    'content' => [
                        'eyebrow' => 'Overview',
                        'title' => 'How we serve '.$i['title'],
                        'alignment' => 'left',
                        'max_width' => 'medium',
                        'body_html' => '<p>'.$i['body'].'</p>',
                    ],
                    'sort_order' => 2,
                    'is_active' => true,
                ]
            );

            // Section 3: solutions filtered to this industry
            PageSection::updateOrCreate(
                ['sectionable_type' => $industry->getMorphClass(), 'sectionable_id' => $industry->id, 'identifier' => 'industry-solutions'],
                [
                    'type' => 'featured_solutions_grid',
                    'content' => [
                        'eyebrow' => 'Solutions',
                        'title' => 'Solutions for '.$i['title'],
                        'columns' => 3,
                        'max_items' => 6,
                        'show_buttons_on_cards' => true,
                        'card_button_label' => 'Learn more',
                    ],
                    'sort_order' => 3,
                    'is_active' => true,
                ]
            );

            // Section 4: CTA
            PageSection::updateOrCreate(
                ['sectionable_type' => $industry->getMorphClass(), 'sectionable_id' => $industry->id, 'identifier' => 'industry-cta'],
                [
                    'type' => 'cta_section',
                    'content' => [
                        'title' => 'Plan a deployment for '.$i['title'],
                        'subtitle' => "We'll scope hardware, dashboards, and a rollout plan with your team.",
                        'alignment' => 'center',
                        'bg' => ['kind' => 'color', 'color_token' => 'accent'],
                        'buttons' => [
                            ['label' => 'Book a call', 'link' => '/contact?industry='.$i['slug'], 'icon' => 'Calendar', 'variant' => 'primary'],
                        ],
                    ],
                    'sort_order' => 4,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Industries seeded ('.Industry::count().' industries with sections).');
    }
}
