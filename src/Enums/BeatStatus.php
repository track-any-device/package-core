<?php

namespace TrackAnyDevice\Core\Enums;

enum BeatStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            BeatStatus::Active => 'Active',
            BeatStatus::Inactive => 'Inactive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            BeatStatus::Active => 'success',
            BeatStatus::Inactive => 'gray',
        };
    }
}
