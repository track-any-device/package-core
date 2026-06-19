<?php

namespace TrackAnyDevice\Core\Enums;

/**
 * Device status — slimmed to active | blocked | pending (Workstream G, 2026-06-19).
 * The previous lifecycle values are DEPRECATED and retained only so existing references compile
 * during the migration; the slim-devices migration maps rows to the three values, after which the
 * old cases can be removed (breaking; minor bump).
 */
enum DeviceStatus: string
{
    case Active = 'active';
    case Blocked = 'blocked';
    case Pending = 'pending';

    /** @deprecated mapped to Pending */
    case Warehouse = 'warehouse';
    /** @deprecated mapped to Pending */
    case Registration = 'registration';
    /** @deprecated mapped to Pending */
    case Inventory = 'inventory';
    /** @deprecated mapped to Pending */
    case InTransit = 'in_transit';
    /** @deprecated mapped to Active */
    case Available = 'available';
    /** @deprecated mapped to Active */
    case Assigned = 'assigned';
    /** @deprecated mapped to Active */
    case InService = 'in_service';
    /** @deprecated mapped to Blocked */
    case Maintenance = 'maintenance';
    /** @deprecated mapped to Blocked */
    case Lost = 'lost';
    /** @deprecated mapped to Blocked */
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            DeviceStatus::Active => 'Active',
            DeviceStatus::Blocked => 'Blocked',
            DeviceStatus::Pending => 'Pending',
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
            DeviceStatus::Active, DeviceStatus::Available, DeviceStatus::Assigned, DeviceStatus::InService => 'success',
            DeviceStatus::Pending, DeviceStatus::Warehouse, DeviceStatus::Registration, DeviceStatus::Inventory => 'gray',
            DeviceStatus::InTransit => 'warning',
            DeviceStatus::Blocked, DeviceStatus::Maintenance, DeviceStatus::Lost, DeviceStatus::Retired => 'danger',
        };
    }

    public function isActive(): bool
    {
        return $this === DeviceStatus::Active;
    }

    public function isBlocked(): bool
    {
        return $this === DeviceStatus::Blocked;
    }

    public function isPending(): bool
    {
        return $this === DeviceStatus::Pending;
    }

    public function isOperational(): bool
    {
        return in_array($this, [DeviceStatus::Active, DeviceStatus::Assigned, DeviceStatus::InService], true);
    }
}
