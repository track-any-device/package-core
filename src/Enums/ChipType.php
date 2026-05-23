<?php

namespace TrackAnyDevice\Core\Enums;

enum ChipType: string
{
    case Gnss = 'gnss';
    case Cellular = 'cellular';
    case Bluetooth = 'bluetooth';
    case Wifi = 'wifi';
    case Mcu = 'mcu';
    case Combo = 'combo';

    public function label(): string
    {
        return match ($this) {
            self::Gnss => 'GNSS',
            self::Cellular => 'Cellular',
            self::Bluetooth => 'Bluetooth',
            self::Wifi => 'Wi-Fi',
            self::Mcu => 'MCU',
            self::Combo => 'Combo',
        };
    }
}
