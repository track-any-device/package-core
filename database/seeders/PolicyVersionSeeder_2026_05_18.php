<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Models\PolicyVersion;
use Illuminate\Database\Seeder;

/**
 * Cookie Policy v1.1.0 — accompanies the new CookieBanner consent UI.
 *
 * Triggered by: rolling out the in-app cookie banner (Accept all / Reject
 * non-essential / Manage preferences) on every page. This changes which
 * cookies the platform sets and how user consent is captured, so the
 * cookie policy version is bumped per the compliance rule documented at
 * the top of TrackAnyDevice\Core\Models\PolicyVersion.
 *
 * Idempotent: re-running this seeder will not duplicate rows because
 * the (type, version) pair is unique and the seeder uses
 * updateOrCreate. Previous versions stay intact (history is preserved
 * and remains reachable via /cookies/1.0.0).
 */
class PolicyVersionSeeder_2026_05_18 extends Seeder
{
    public function run(): void
    {
        $row = PolicyVersion::updateOrCreate(
            ['type' => PolicyVersion::TYPE_COOKIE, 'version' => '1.1.0'],
            [
                'title' => 'Cookie Policy',
                'content' => self::body(),
                'effective_from' => '2026-05-18',
                'is_current' => true,
            ],
        );

        // Demote any prior current cookie policy row.
        $row->setCurrent();
    }

    private static function body(): string
    {
        return <<<'MD'
        # Cookie Policy

        **Version 1.1.0 — Effective 2026-05-18**

        This policy explains how our platform uses cookies and similar
        technologies. It replaces version 1.0.0; prior versions remain
        available under `/cookies/{version}` for audit purposes.

        ## 1. What we store

        We currently use three categories of cookies:

        - **Essential** — required for authentication (session cookies,
          CSRF tokens) and to remember your light/dark theme choice. These
          are always on; the platform does not function without them.
        - **Analytics** — opt-in. Reserved for usage-pattern analytics in
          a future release. No analytics cookies are set today.
        - **Marketing** — opt-in. Reserved for feature announcement
          targeting. No marketing cookies are set today.

        ## 2. How you control them

        On first visit, you will see a consent banner with three options:

        1. **Accept all** — enables analytics and marketing cookies.
        2. **Reject non-essential** — keeps only the essential category.
        3. **Manage preferences** — choose each category individually.

        Your choice is persisted in your browser's `localStorage` under
        the key `cookie-consent` and respected on every subsequent visit.

        ## 3. Changing your choice later

        Clear the `cookie-consent` entry from your browser storage and
        reload the page — the banner will re-appear and you can re-choose.

        ## 4. Contact

        Questions about this policy: contact the platform administrator.
        MD;
    }
}
