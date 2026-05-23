<?php

namespace TrackAnyDevice\Core\Enums;

use TrackAnyDevice\Core\Models\ChargingSet;
use TrackAnyDevice\Core\Models\Chip;
use TrackAnyDevice\Core\Models\ComputeBoard;
use TrackAnyDevice\Core\Models\ConnectingCable;
use TrackAnyDevice\Core\Models\DeviceType;

enum ProductType: string
{
    case DeviceType = 'device_type';
    case Chip = 'chip';
    case ComputeBoard = 'compute_board';
    case ConnectingCable = 'connecting_cable';
    case ChargingSet = 'charging_set';

    public function label(): string
    {
        return match ($this) {
            self::DeviceType => 'Device Type',
            self::Chip => 'Chip',
            self::ComputeBoard => 'Compute Board',
            self::ConnectingCable => 'Connecting Cable',
            self::ChargingSet => 'Charging Set',
        };
    }

    public function modelClass(): string
    {
        return match ($this) {
            self::DeviceType => DeviceType::class,
            self::Chip => Chip::class,
            self::ComputeBoard => ComputeBoard::class,
            self::ConnectingCable => ConnectingCable::class,
            self::ChargingSet => ChargingSet::class,
        };
    }
}
