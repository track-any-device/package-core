<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Enums;

/**
 * Defines the violation semantics of a beat zone.
 *
 * Inclusion  — device must stay INSIDE.  Alert triggers when it exits.
 *              Supports multi-level escalation via the parent-beat chain.
 *
 * Exclusion  — device must stay OUTSIDE. Alert triggers when it enters.
 *              No chain escalation — a single level-1 incident is raised
 *              for the duration the device remains inside the zone.
 */
enum BeatZoneType: string
{
    case Inclusion = 'inclusion';
    case Exclusion = 'exclusion';

    public function label(): string
    {
        return match ($this) {
            self::Inclusion => 'Inclusion Zone',
            self::Exclusion => 'Exclusion Zone',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Inclusion => 'Device must remain inside this zone. Incident raised on exit.',
            self::Exclusion => 'Device must stay outside this zone. Incident raised on entry.',
        };
    }
}
