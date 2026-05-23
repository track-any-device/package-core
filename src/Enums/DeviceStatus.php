<?php

namespace TrackAnyDevice\Core\Enums;

enum DeviceStatus: string
{
    case Warehouse = 'warehouse';
    case Registration = 'registration';
    case Inventory = 'inventory';
    case InTransit = 'in_transit';
    case Available = 'available';
    case Assigned = 'assigned';
    case InService = 'in_service';
    case Maintenance = 'maintenance';
    case Lost = 'lost';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            DeviceStatus::Warehouse => 'Warehouse',
            DeviceStatus::Registration => 'Registration',
            DeviceStatus::Inventory => 'Inventory',
            DeviceStatus::InTransit => 'In Transit',
            DeviceStatus::Available => 'Available',
            DeviceStatus::Assigned => 'Assigned',
            DeviceStatus::InService => 'In Service',
            DeviceStatus::Maintenance => 'Maintenance',
            DeviceStatus::Lost => 'Lost',
            DeviceStatus::Retired => 'Retired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            DeviceStatus::Warehouse => 'gray',
            DeviceStatus::Registration => 'info',
            DeviceStatus::Inventory => 'gray',
            DeviceStatus::InTransit => 'warning',
            DeviceStatus::Available => 'success',
            DeviceStatus::Assigned => 'primary',
            DeviceStatus::InService => 'primary',
            DeviceStatus::Maintenance => 'warning',
            DeviceStatus::Lost => 'danger',
            DeviceStatus::Retired => 'gray',
        };
    }

    public function isOperational(): bool
    {
        return in_array($this, [DeviceStatus::Assigned, DeviceStatus::InService]);
    }
}
