<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Console\Commands;

use TrackAnyDevice\Core\Models\Beat;
use TrackAnyDevice\Core\Services\GeoFence;
use Illuminate\Console\Command;

/**
 * One-time migration: converts every beat that still stores circle data
 * ({lat, lng, radius}) to a 64-point polygon vertex array and sets
 * geo_fence_type = 'polygon'.
 *
 * Safe to run multiple times — beats already in polygon format are skipped.
 *
 * Usage:
 *   php artisan beats:normalize-to-polygon
 *   php artisan beats:normalize-to-polygon --dry-run
 */
class NormalizeBeatsToPolygon extends Command
{
    protected $signature = 'beats:normalize-to-polygon
                            {--dry-run : Preview changes without writing to the database}';

    protected $description = 'Convert all circle-format beat coordinates ({lat,lng,radius}) to polygon vertex arrays.';

    public function handle(GeoFence $geo): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $beats = Beat::whereNotNull('coordinates')->get();
        $converted = 0;
        $skipped = 0;

        foreach ($beats as $beat) {
            $coords = $beat->coordinates;

            // Already a flat vertex array — nothing to do
            if (is_array($coords) && array_is_list($coords)) {
                $skipped++;

                continue;
            }

            // Circle format: {lat, lng, radius}
            if (! isset($coords['lat'], $coords['lng'], $coords['radius'])) {
                $this->warn("Beat #{$beat->id} \"{$beat->name}\" has unknown coordinate format — skipped.");
                $skipped++;

                continue;
            }

            $normalised = $geo->normaliseToPolygon($coords);

            $this->line(sprintf(
                'Converting beat #%d "%s" (circle r=%.0f m → %d polygon vertices)',
                $beat->id,
                $beat->name,
                $coords['radius'],
                count($normalised['coordinates']),
            ));

            if (! $dryRun) {
                $beat->update([
                    'geo_fence_type' => $normalised['type'],
                    'coordinates' => $normalised['coordinates'],
                ]);
            }

            $converted++;
        }

        $this->info(sprintf(
            '%s%d beat(s) converted, %d already in polygon format.',
            $dryRun ? '[DRY RUN] ' : '',
            $converted,
            $skipped,
        ));

        return self::SUCCESS;
    }
}
