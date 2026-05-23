<?php

namespace TrackAnyDevice\Core\Enums;

enum DeviceCommandStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Acknowledged = 'acknowledged';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            DeviceCommandStatus::Pending => 'Pending',
            DeviceCommandStatus::Queued => 'Queued',
            DeviceCommandStatus::Sent => 'Sent',
            DeviceCommandStatus::Delivered => 'Delivered',
            DeviceCommandStatus::Acknowledged => 'Acknowledged',
            DeviceCommandStatus::Failed => 'Failed',
            DeviceCommandStatus::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            DeviceCommandStatus::Pending => 'gray',
            DeviceCommandStatus::Queued => 'info',
            DeviceCommandStatus::Sent => 'primary',
            DeviceCommandStatus::Delivered => 'success',
            DeviceCommandStatus::Acknowledged => 'success',
            DeviceCommandStatus::Failed => 'danger',
            DeviceCommandStatus::Cancelled => 'gray',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            DeviceCommandStatus::Delivered,
            DeviceCommandStatus::Acknowledged,
            DeviceCommandStatus::Failed,
            DeviceCommandStatus::Cancelled,
        ]);
    }
}
