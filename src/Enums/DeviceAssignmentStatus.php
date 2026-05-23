<?php

namespace TrackAnyDevice\Core\Enums;

enum DeviceAssignmentStatus: string
{
    case Active = 'active';
    case Returned = 'returned';
    case Transferred = 'transferred';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            DeviceAssignmentStatus::Active => 'Active',
            DeviceAssignmentStatus::Returned => 'Returned',
            DeviceAssignmentStatus::Transferred => 'Transferred',
            DeviceAssignmentStatus::Lost => 'Lost',
        };
    }

    public function color(): string
    {
        return match ($this) {
            DeviceAssignmentStatus::Active => 'success',
            DeviceAssignmentStatus::Returned => 'gray',
            DeviceAssignmentStatus::Transferred => 'info',
            DeviceAssignmentStatus::Lost => 'danger',
        };
    }

    public function isActive(): bool
    {
        return $this === DeviceAssignmentStatus::Active;
    }
}
