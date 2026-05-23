<?php

namespace TrackAnyDevice\Core\Observers;

use TrackAnyDevice\Core\Models\BeatTemplate;

/**
 * When an admin edits a beat template, every beat using it is
 * re-synced so the curated polygon stays the source of truth.
 *
 * Triggers only when the geometry actually changed (coordinates or
 * geo_fence_type) — purely-cosmetic edits (name, description) don't
 * touch downstream beats. Version is auto-bumped on every geometry
 * change so the admin can see how many revisions a template has had.
 *
 * Uses the `updating` / `updated` hooks (not `saving` / `saved`) so
 * the lifecycle is unambiguous — `wasRecentlyCreated` doesn't help
 * because Laravel doesn't unset it on subsequent updates of the same
 * in-memory instance.
 */
class BeatTemplateObserver
{
    /**
     * Tracks per-template whether the geometry was dirty when the
     * update started, so the `updated()` callback can re-sync after
     * the row has been written.
     *
     * @var array<int, bool>
     */
    private static array $geometryDirty = [];

    public function creating(BeatTemplate $template): void
    {
        // Force a sane default for version. The DB column has
        // default(1) but SQLite doesn't always honour it for an
        // unsignedInteger that's omitted from the INSERT.
        if ($template->version === null) {
            $template->version = 1;
        }
    }

    public function updating(BeatTemplate $template): void
    {
        $geometryDirty = $template->isDirty('coordinates') || $template->isDirty('geo_fence_type');

        if ($geometryDirty) {
            $template->version = ((int) ($template->getOriginal('version') ?? 1)) + 1;
        }

        self::$geometryDirty[$template->getKey()] = $geometryDirty;
    }

    public function updated(BeatTemplate $template): void
    {
        $wasGeometryDirty = self::$geometryDirty[$template->getKey()] ?? false;
        unset(self::$geometryDirty[$template->getKey()]);

        if ($wasGeometryDirty) {
            $template->syncBeats();
        }
    }
}
