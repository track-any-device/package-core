<?php

namespace TrackAnyDevice\Core\Enums;

use TrackAnyDevice\Core\Models\DeviceType;

/**
 * Sellable product kinds. After the catalog cut (2026-06), DeviceType is the only app-side
 * sellable; accessories and CMS content live in Sanity.
 */
enum ProductType: string
{
    case DeviceType = 'device_type';

    public function label(): string
    {
        return match ($this) {
            self::DeviceType => 'Device Type',
        };
    }

    public function modelClass(): string
    {
        return match ($this) {
            self::DeviceType => DeviceType::class,
        };
    }
}
