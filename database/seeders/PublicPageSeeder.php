<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Models\Page;
use TrackAnyDevice\Core\Models\PageSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Seeds CMS Page records and their sections for the public navigation:
 * products, solutions, about, contact, blog.
 *
 * Uses the 12-component schema. Re-running is safe.
 */
class PublicPageSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedProductsPage();
        $this->seedSolutionsPage();
        $this->seedAboutPage();
        $this->seedContactPage();
        $this->seedBlogPage();

        $this->command?->info('Public pages seeded: products + solutions + about + contact + blog.');
    }

    private function seedProductsPage(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'products'],
            [
                'title' => 'Products',
                'meta_title' => 'Products — Track Any Device',
                'meta_description' => 'Browse GPS trackers, sensors, IoT gateways, compute boards, cables, and accessories.',
                'is_active' => true,
            ]
        );

        $sort = 0;

        $this->upsert($page, 'hero', 'products-hero', ++$sort, [
            'size' => 'half',
            'alignment' => 'center',
            'eyebrow' => 'Catalog',
            'title' => 'Hardware for every operation',
            'subtitle' => 'Reliable tracking devices, sensors, compute boards, and accessories.',
            'bg' => ['kind' => 'gradient', 'gradient_from' => '#1e293b', 'gradient_to' => '#0f172a', 'gradient_direction' => 'to-br'],
            'buttons' => [
                ['label' => 'Browse catalog', 'link' => '#products', 'icon' => 'ArrowDown', 'variant' => 'primary'],
                ['label' => 'Contact sales',  'link' => '/contact',  'icon' => 'Mail', 'variant' => 'outline'],
            ],
        ]);

        $this->upsert($page, 'cards_grid', 'product-promises', ++$sort, [
            'columns' => 4,
            'card_style' => 'icon',
            'title' => 'Why buy from us',
            'items' => [
                ['icon' => 'Shield',     'title' => '1-Year Warranty',  'description' => 'Every device covered.'],
                ['icon' => 'Truck',      'title' => 'Fast Shipping',     'description' => 'Worldwide delivery.'],
                ['icon' => 'CheckCircle', 'title' => 'Secure Payment',   'description' => '100% safe checkout.'],
                ['icon' => 'RefreshCw',  'title' => '30-Day Returns',    'description' => 'No-fuss guarantee.'],
            ],
        ]);

        $this->upsert($page, 'products_with_filter', 'products', ++$sort, [
            'title' => 'Shop tracking devices',
            'subtitle' => 'Filter by category, use case, or connectivity.',
            'cta_order_href' => '/store',
            'items_per_page' => 12,
            'categories' => [
                ['label' => 'All Products',   'value' => 'all'],
                ['label' => 'GPS Trackers',   'value' => 'gps'],
                ['label' => 'Sensors',         'value' => 'sensor'],
                ['label' => 'IoT Gateways',   'value' => 'gateway'],
                ['label' => 'Asset Tags',     'value' => 'tag'],
                ['label' => 'Compute Boards', 'value' => 'compute'],
                ['label' => 'Accessories',    'value' => 'accessory'],
            ],
            'use_cases' => [
                ['label' => 'Fleet Management', 'value' => 'fleet'],
                ['label' => 'Asset Tracking',   'value' => 'asset'],
                ['label' => 'Cold Chain',       'value' => 'cold-chain'],
                ['label' => 'Agriculture',      'value' => 'agriculture'],
                ['label' => 'Water Management', 'value' => 'water'],
                ['label' => 'Smart City',       'value' => 'smart-city'],
            ],
            'connectivity' => [
                ['label' => '4G LTE',  'value' => '4g'],
                ['label' => '2G',      'value' => '2g'],
                ['label' => 'NB-IoT',  'value' => 'nb-iot'],
                ['label' => 'LoRaWAN', 'value' => 'lora'],
                ['label' => 'Wi-Fi',   'value' => 'wifi'],
            ],
        ]);

        $this->upsert($page, 'cta_section', 'products-cta', ++$sort, [
            'eyebrow' => 'Bulk orders',
            'title' => 'Need 100+ devices?',
            'subtitle' => 'Our team will scope, price, and ship a custom hardware bundle for your fleet.',
            'alignment' => 'center',
            'bg' => ['kind' => 'color', 'color_token' => 'accent'],
            'buttons' => [
                ['label' => 'Talk to sales', 'link' => '/contact', 'icon' => 'Mail', 'variant' => 'primary'],
            ],
        ]);
    }

    private function seedSolutionsPage(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'solutions'],
            [
                'title' => 'Solutions',
                'meta_title' => 'Solutions — Track Any Device',
                'meta_description' => 'Industry solutions: fleet management, asset tracking, cold chain, agriculture, water, smart city.',
                'is_active' => true,
            ]
        );

        $sort = 0;

        $this->upsert($page, 'hero', 'solutions-hero', ++$sort, [
            'size' => 'third',
            'alignment' => 'center',
            'eyebrow' => 'Solutions',
            'title' => 'Solutions for every industry',
            'title_highlight' => 'every industry',
            'subtitle' => 'From government to enterprise — connected operations in one platform.',
            // Brand-tinted gradient with built-in dark vignette (added in
            // SectionBackground) keeps the hero readable without a busy
            // pattern. Deeper end-stops give the white text more contrast
            // than the old `#1e3a8a → #1e40af` pair.
            'bg' => ['kind' => 'gradient', 'gradient_from' => '#0b1220', 'gradient_to' => '#1e3a8a', 'gradient_direction' => 'to-br'],
            'buttons' => [
                ['label' => 'Contact sales', 'link' => '/contact', 'icon' => 'Mail', 'variant' => 'primary'],
                ['label' => 'Browse products', 'link' => '/products', 'icon' => 'ShoppingCart', 'variant' => 'outline'],
            ],
        ]);

        $this->upsert($page, 'solutions_with_filter', 'all-solutions', ++$sort, [
            'title' => 'All solutions',
            'subtitle' => 'Pick a solution to see hardware, dashboards, and references.',
            'items_per_page' => 12,
            'filters' => [
                'industries' => [
                    ['label' => 'Government',  'value' => 'government'],
                    ['label' => 'Enterprise',  'value' => 'enterprise'],
                    ['label' => 'SMB',         'value' => 'smb'],
                    ['label' => 'Consumer',    'value' => 'consumer'],
                ],
            ],
        ]);

        // Trailing cta_section removed — the solutions_with_filter section
        // already covers the call to action via its own card-level CTAs,
        // and the extra CTA card created visual clutter at the bottom of
        // the page.
        PageSection::where('sectionable_type', $page::class)
            ->where('sectionable_id', $page->id)
            ->where('identifier', 'solutions-cta')
            ->delete();
    }

    private function seedAboutPage(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'about'],
            [
                'title' => 'About',
                'meta_title' => 'About — Track Any Device',
                'meta_description' => 'About Track Any Device — our mission, our team, and our commitment to reliable IoT tracking.',
                'is_active' => true,
            ]
        );

        $sort = 0;

        // About page opens with a text section instead of a hero — the
        // page is a story page, not a marketing landing, so leading with
        // a hero felt salesy. Buttons that lived on the old hero moved
        // into the closing CTA.
        $this->upsert($page, 'text_section', 'about-intro', ++$sort, [
            'eyebrow' => 'Our story',
            'title' => 'On a mission to make every device traceable',
            'alignment' => 'center',
            'max_width' => 'medium',
            'body_html' => '<p>Telemetry should be a commodity, not a moat. We build open hardware, open protocols, and a self-hostable platform so any team — from a hobbyist to a national agency — can monitor what they own without surrendering their data.</p>',
        ]);

        $this->upsert($page, 'text_section', 'about-mission', ++$sort, [
            'eyebrow' => 'Our mission',
            'title' => 'What we stand for',
            'alignment' => 'left',
            'max_width' => 'medium',
            'body_html' => '<p>Three principles drive every decision: <strong>every device welcome</strong> (from a $3 ESP8266 to enterprise-grade hardware), <strong>self-hosted &amp; private</strong> (your telemetry stays on infrastructure you control), and <strong>real-time by default</strong> (sub-second updates over WebSocket with a uniform signal schema).</p>',
        ]);

        $this->upsert($page, 'cards_grid', 'about-stats', ++$sort, [
            'columns' => 4,
            'card_style' => 'stat',
            'items' => [
                ['icon' => 'Cpu',         'title' => 'Devices tracked',  'value' => '10,000+'],
                ['icon' => 'Zap',         'title' => 'Platform uptime',  'value' => '99.9%'],
                ['icon' => 'Headphones',  'title' => 'Operations team',  'value' => '24/7'],
                ['icon' => 'Building2',   'title' => 'Industries served', 'value' => '6+'],
            ],
        ]);

        $this->upsert($page, 'banner_5050', 'about-team', ++$sort, [
            'image' => '/hero.png',
            'image_position' => 'left',
            'eyebrow' => 'Our team',
            'title' => 'Hardware engineers, platform builders, ops specialists',
            'body' => 'A small focused team that has shipped tracking platforms for governments, fleet operators, and IoT solution providers across three continents.',
            'bullets' => [
                ['icon' => 'Cpu',     'text' => 'Hardware design and protocol engineering'],
                ['icon' => 'Cloud',   'text' => 'Cloud platform and real-time infrastructure'],
                ['icon' => 'Headphones', 'text' => '24/7 operations and customer success'],
            ],
            'buttons' => [
                ['label' => 'Talk to us', 'link' => '/contact', 'icon' => 'Mail', 'variant' => 'primary'],
            ],
        ]);

        $this->upsert($page, 'cta_section', 'about-cta', ++$sort, [
            'title' => 'See it in your own operation',
            'subtitle' => "Book a 30-minute call and we'll spec the right hardware and dashboards.",
            'alignment' => 'center',
            'bg' => ['kind' => 'color', 'color_token' => 'accent'],
            'buttons' => [
                ['label' => 'Book a call', 'link' => '/contact?topic=demo', 'icon' => 'Calendar', 'variant' => 'primary'],
            ],
        ]);
    }

    private function seedContactPage(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'contact'],
            [
                'title' => 'Contact Us',
                'meta_title' => 'Contact — Track Any Device',
                'meta_description' => 'Get in touch with our team — sales enquiries, support questions, partnership requests.',
                'is_active' => true,
            ]
        );

        $sort = 0;

        $this->upsert($page, 'hero', 'contact-hero', ++$sort, [
            'size' => 'third',
            'alignment' => 'center',
            'eyebrow' => 'Get in touch',
            'title' => 'Talk to us',
            'subtitle' => 'Sales, support, partnerships — we read every message.',
            'bg' => ['kind' => 'color', 'color_token' => 'muted'],
        ]);

        $this->upsert($page, 'contact_form', 'contact-form', ++$sort, [
            'title' => 'Send us a message',
            'subtitle' => "Drop your details and we'll reply within one business day. For urgent operational issues, use the phone or email shown.",
            'fields' => ['name', 'email', 'phone', 'company', 'subject', 'message'],
            'submit_label' => 'Send message',
            'success_message' => "Thanks — we'll reply within one business day.",
            'contact_info' => [
                'phone' => '+92 300 0000000',
                'email' => 'hello@trackanydevice.com',
                'address' => 'Lahore, Pakistan',
            ],
        ]);
    }

    private function seedBlogPage(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'blog'],
            [
                'title' => 'Blog',
                'meta_title' => 'Blog — Track Any Device',
                'meta_description' => 'Engineering notes, customer stories, and platform updates.',
                'is_active' => true,
            ]
        );

        $sort = 0;

        $this->upsert($page, 'hero', 'blog-hero', ++$sort, [
            'size' => 'third',
            'alignment' => 'center',
            'eyebrow' => 'Insights',
            'title' => 'From the team',
            'subtitle' => 'Engineering notes, customer stories, product updates.',
            'bg' => ['kind' => 'color', 'color_token' => 'muted'],
        ]);

        $this->upsert($page, 'featured_blog_slider', 'blog-featured', ++$sort, [
            'title' => 'Editor picks',
            'subtitle' => 'Highlighted stories from the last few weeks.',
            'featured_blog_slug' => null,
            'list_count' => 5,
        ]);

        $this->upsert($page, 'blogs_listing', 'blog-listing', ++$sort, [
            'title' => 'All articles',
            'items_per_page' => 12,
            'show_tag_filter' => true,
            'show_author_filter' => false,
        ]);
    }

    private function upsert(Model $parent, string $type, string $identifier, int $sortOrder, array $content): void
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
