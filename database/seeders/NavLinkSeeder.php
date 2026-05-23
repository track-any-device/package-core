<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Models\NavLink;
use Illuminate\Database\Seeder;

class NavLinkSeeder extends Seeder
{
    public function run(): void
    {
        // ── Header (top navigation) ─────────────────────────────────────────
        $header = [
            ['label' => 'Home', 'href' => '/', 'sort_order' => 1],
            ['label' => 'Products', 'href' => '/products', 'sort_order' => 2],
            ['label' => 'Solutions', 'href' => '/solutions', 'sort_order' => 3],
            ['label' => 'TAD101 Docs', 'href' => '/docs/tad101', 'sort_order' => 4],
            ['label' => 'About', 'href' => '/about', 'sort_order' => 5],
            ['label' => 'Contact Us', 'href' => '/contact', 'sort_order' => 6],
        ];

        // ── Footer columns ──────────────────────────────────────────────────
        // Column 1 mirrors the header (Quick Links). Column 2 is policy /
        // legal pages. Column 3 in SiteFooter is the subscription form, not
        // a nav-link group — so no rows here for that.
        $footerQuick = [
            ['label' => 'Home', 'href' => '/', 'sort_order' => 1],
            ['label' => 'Products', 'href' => '/products', 'sort_order' => 2],
            ['label' => 'Solutions', 'href' => '/solutions', 'sort_order' => 3],
            ['label' => 'About', 'href' => '/about', 'sort_order' => 4],
            ['label' => 'Contact Us', 'href' => '/contact', 'sort_order' => 5],
        ];

        $footerLegal = [
            ['label' => 'Terms of Service', 'href' => '/terms', 'sort_order' => 1],
            ['label' => 'Privacy Policy', 'href' => '/privacy', 'sort_order' => 2],
            ['label' => 'Cookie Policy', 'href' => '/cookies', 'sort_order' => 3],
        ];

        $this->upsertGroup(NavLink::PLACEMENT_HEADER, $header);
        $this->upsertGroup(NavLink::PLACEMENT_FOOTER_QUICK, $footerQuick);
        $this->upsertGroup(NavLink::PLACEMENT_FOOTER_LEGAL, $footerLegal);

        // Deactivate stale header/footer-quick rows that don't match the new
        // set so a re-seed reflects the new IA without surfacing old labels.
        NavLink::where('placement', NavLink::PLACEMENT_HEADER)
            ->whereNotIn('label', collect($header)->pluck('label'))
            ->update(['is_active' => false]);

        NavLink::where('placement', NavLink::PLACEMENT_FOOTER_QUICK)
            ->whereNotIn('label', collect($footerQuick)->pluck('label'))
            ->update(['is_active' => false]);

        // The new 3-column footer doesn't render the "Support" group, so
        // mark anything seeded previously as inactive to keep things tidy.
        NavLink::where('placement', NavLink::PLACEMENT_FOOTER_SUPPORT)
            ->update(['is_active' => false]);

        $this->command?->info('Nav links seeded: header + footer quick + footer legal.');
    }

    /**
     * @param  array<int, array{label:string, href:string, sort_order:int}>  $rows
     */
    private function upsertGroup(string $placement, array $rows): void
    {
        foreach ($rows as $row) {
            NavLink::updateOrCreate(
                ['placement' => $placement, 'label' => $row['label']],
                [
                    'href' => $row['href'],
                    'target' => '_self',
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
