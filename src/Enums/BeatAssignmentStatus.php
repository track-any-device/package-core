<?php

namespace TrackAnyDevice\Core\Enums;

enum BeatAssignmentStatus: string
{
    case Active = 'active';
    case Ended = 'ended';
    case Transferred = 'transferred';

    public function label(): string
    {
        return match ($this) {
            BeatAssignmentStatus::Active => 'Active',
            BeatAssignmentStatus::Ended => 'Ended',
            BeatAssignmentStatus::Transferred => 'Transferred',
        };
    }

    public function color(): string
    {
        return match ($this) {
            BeatAssignmentStatus::Active => 'success',
            BeatAssignmentStatus::Ended => 'gray',
            BeatAssignmentStatus::Transferred => 'info',
        };
    }

    public function isActive(): bool
    {
        return $this === BeatAssignmentStatus::Active;
    }
}
