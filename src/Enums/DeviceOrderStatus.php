<?php

namespace TrackAnyDevice\Core\Enums;

enum DeviceOrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            DeviceOrderStatus::Pending => 'Pending',
            DeviceOrderStatus::Confirmed => 'Confirmed',
            DeviceOrderStatus::Delivered => 'Delivered',
            DeviceOrderStatus::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            DeviceOrderStatus::Pending => 'warning',
            DeviceOrderStatus::Confirmed => 'info',
            DeviceOrderStatus::Delivered => 'success',
            DeviceOrderStatus::Cancelled => 'danger',
        };
    }
}
