<?php

namespace TrackAnyDevice\Core\Enums;

enum WorkingMode: string
{
    case PowerSaving = 'power_saving';
    case Balanced = 'balanced';
    case Realtime = 'realtime';
    case Vibration = 'vibration';
    case Continuous = 'continuous';

    public function label(): string
    {
        return match ($this) {
            self::PowerSaving => 'Power Saving',
            self::Balanced => 'Balanced',
            self::Realtime => 'Real-time',
            self::Vibration => 'Vibration / Smart',
            self::Continuous => 'Continuous',
        };
    }

    public function jt808Bits(): int
    {
        return match ($this) {
            self::PowerSaving => 0b000,
            self::Balanced => 0b001,
            self::Realtime => 0b010,
            self::Vibration => 0b011,
            self::Continuous => 0b100,
        };
    }

    public static function fromJt808Bits(int $bits): ?self
    {
        return match ($bits & 0b111) {
            0b000 => self::PowerSaving,
            0b001 => self::Balanced,
            0b010 => self::Realtime,
            0b011 => self::Vibration,
            0b100 => self::Continuous,
            default => null,
        };
    }
}
