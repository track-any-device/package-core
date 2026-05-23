<?php

namespace TrackAnyDevice\Core\Enums;

enum AssigneeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case OnLeave = 'on_leave';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            AssigneeStatus::Active => 'Active',
            AssigneeStatus::Inactive => 'Inactive',
            AssigneeStatus::OnLeave => 'On Leave',
            AssigneeStatus::Terminated => 'Terminated',
        };
    }

    public function color(): string
    {
        return match ($this) {
            AssigneeStatus::Active => 'success',
            AssigneeStatus::Inactive => 'gray',
            AssigneeStatus::OnLeave => 'warning',
            AssigneeStatus::Terminated => 'danger',
        };
    }

    public function isActive(): bool
    {
        return $this === AssigneeStatus::Active;
    }
}
