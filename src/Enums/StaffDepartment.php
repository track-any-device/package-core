<?php

namespace TrackAnyDevice\Core\Enums;

enum StaffDepartment: string
{
    case Engineering = 'engineering';
    case Procurement = 'procurement';
    case Warehouse = 'warehouse';
    case Marketing = 'marketing';
    case Sales = 'sales';
    case CoreTeam = 'core_team';

    public function label(): string
    {
        return match ($this) {
            self::Engineering => 'Engineering',
            self::Procurement => 'Procurement',
            self::Warehouse => 'Warehouse',
            self::Marketing => 'Marketing',
            self::Sales => 'Sales',
            self::CoreTeam => 'Core Team',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Engineering => 'info',
            self::Procurement => 'warning',
            self::Warehouse => 'success',
            self::Marketing => 'primary',
            self::Sales => 'danger',
            self::CoreTeam => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Engineering => 'heroicon-o-wrench-screwdriver',
            self::Procurement => 'heroicon-o-truck',
            self::Warehouse => 'heroicon-o-building-storefront',
            self::Marketing => 'heroicon-o-megaphone',
            self::Sales => 'heroicon-o-currency-dollar',
            self::CoreTeam => 'heroicon-o-shield-check',
        };
    }
}
