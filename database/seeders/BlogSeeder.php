<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Models\Blog;
use TrackAnyDevice\Core\Models\BlogTag;
use TrackAnyDevice\Core\Models\PageSection;
use TrackAnyDevice\Core\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds sample Blog posts + tags. Each post wires up its body as a small
 * stack of PageSections via the polymorphic `sections` morph relation —
 * exactly the same component model used by Pages, Solutions, Industries.
 */
class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['name' => 'Engineering', 'slug' => 'engineering', 'color' => '#1565c0'],
            ['name' => 'Product',     'slug' => 'product',     'color' => '#2e7d32'],
            ['name' => 'Customer',    'slug' => 'customer',    'color' => '#7c3aed'],
            ['name' => 'Hardware',    'slug' => 'hardware',    'color' => '#f57c00'],
        ];
        foreach ($tags as $t) {
            BlogTag::updateOrCreate(['slug' => $t['slug']], $t);
        }

        $author = User::where('role', 'admin')->first();

        $posts = [
            [
                'title' => 'Introducing TAD101 — our universal device protocol',
                'slug' => 'introducing-tad101',
                'excerpt' => 'Why we built a single protocol that covers Arduino, Raspberry Pi, mobile apps, and off-the-shelf trackers.',
                'cover_image' => null,
                'is_featured' => true,
                'sort_order' => 1,
                'tags' => ['engineering', 'product'],
                'body' => "<p>For the last year we've watched teams stitch together GPS trackers, sensor boards, and mobile apps with five different protocols. Maintaining them is exhausting.</p><p>TAD101 is one protocol with a JSON envelope, a uniform signal schema, and SDK samples for every common platform. This post walks through why we built it, what it looks like on the wire, and how to ship a board on it in an afternoon.</p>",
            ],
            [
                'title' => 'Suthra Punjab — 12 months of operational telemetry',
                'slug' => 'suthra-punjab-case-study',
                'excerpt' => 'How the Environment Protection Department monitors 3,000 sanitation workers across 80 beats in real time.',
                'cover_image' => null,
                'is_featured' => true,
                'sort_order' => 2,
                'tags' => ['customer'],
                'body' => '<p>Suthra Punjab runs one of the largest field-staff tracking deployments in South Asia. This case study covers their rollout — from device procurement to bilingual portal chrome — and the operational outcomes after twelve months.</p>',
            ],
            [
                'title' => 'Choosing the right tracker: GPS vs LTE-Cat-M vs LoRa',
                'slug' => 'tracker-radio-comparison',
                'excerpt' => 'A side-by-side comparison of three popular radio choices for asset tracking.',
                'cover_image' => null,
                'is_featured' => false,
                'sort_order' => 3,
                'tags' => ['hardware'],
                'body' => '<p>Pick the wrong radio and your tracker either burns through batteries or never reports from the warehouse basement. We compare GPS-only, LTE-Cat-M, and LoRaWAN across four scenarios.</p>',
            ],
            [
                'title' => 'Building a self-hosted IoT platform on Laravel + Inertia',
                'slug' => 'self-hosted-iot-platform-stack',
                'excerpt' => 'The full stack behind a multi-tenant IoT platform that scales to 100k+ devices.',
                'cover_image' => null,
                'is_featured' => false,
                'sort_order' => 4,
                'tags' => ['engineering'],
                'body' => '<p>Why Laravel 13, Inertia v3, React 19, stancl/tenancy v3, and shadcn/ui are the right stack for a production IoT platform — and what we learned along the way.</p>',
            ],
        ];

        foreach ($posts as $p) {
            $blog = Blog::updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'title' => $p['title'],
                    'excerpt' => $p['excerpt'],
                    'cover_image' => $p['cover_image'],
                    'author_id' => $author?->id,
                    'published_at' => now()->subDays(5 * $p['sort_order']),
                    'is_active' => true,
                    'is_featured' => $p['is_featured'],
                    'sort_order' => $p['sort_order'],
                ]
            );

            // Attach tags.
            $tagIds = BlogTag::whereIn('slug', $p['tags'])->pluck('id');
            $blog->tags()->sync($tagIds);

            // Section 1: hero
            PageSection::updateOrCreate(
                ['sectionable_type' => $blog->getMorphClass(), 'sectionable_id' => $blog->id, 'identifier' => 'post-hero'],
                [
                    'type' => 'hero',
                    'content' => [
                        'size' => 'half',
                        'alignment' => 'center',
                        'eyebrow' => $p['tags'][0] ?? 'Article',
                        'title' => $p['title'],
                        'subtitle' => $p['excerpt'],
                        'bg' => ['kind' => 'color', 'color_token' => 'muted'],
                    ],
                    'sort_order' => 1,
                    'is_active' => true,
                ]
            );

            // Section 2: text body
            PageSection::updateOrCreate(
                ['sectionable_type' => $blog->getMorphClass(), 'sectionable_id' => $blog->id, 'identifier' => 'post-body'],
                [
                    'type' => 'text_section',
                    'content' => [
                        'alignment' => 'left',
                        'max_width' => 'narrow',
                        'body_html' => $p['body'],
                    ],
                    'sort_order' => 2,
                    'is_active' => true,
                ]
            );

            // Section 3: CTA at end of post
            PageSection::updateOrCreate(
                ['sectionable_type' => $blog->getMorphClass(), 'sectionable_id' => $blog->id, 'identifier' => 'post-cta'],
                [
                    'type' => 'cta_section',
                    'content' => [
                        'title' => 'Want to talk to us?',
                        'subtitle' => 'We respond to every message within one business day.',
                        'alignment' => 'center',
                        'bg' => ['kind' => 'color', 'color_token' => 'accent'],
                        'buttons' => [
                            ['label' => 'Contact us', 'link' => '/contact', 'icon' => 'Mail', 'variant' => 'primary'],
                        ],
                    ],
                    'sort_order' => 3,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Blog seeded ('.Blog::count().' posts, '.BlogTag::count().' tags).');
    }
}
