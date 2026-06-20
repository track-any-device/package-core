<?php

namespace TrackAnyDevice\Core\Enums;

/**
 * Single-Role permission model (restructure 2026-06, Workstream F).
 *
 * Admin (Filament + delete) · Core (Filament, no delete) · Procurement / Workshop / DeliveryOrder
 * (web operations portals) · TenantUser (owns tenants) · User (web/app "my" portal).
 */
enum Role: string
{
    case Admin = 'admin';                  // Filament admin — full access incl. delete
    case Core = 'core';                    // Filament admin — all resources, NO delete
    case Procurement = 'procurement';      // web operations portal
    case Workshop = 'workshop';            // web operations portal
    case DeliveryOrder = 'delivery_order'; // web operations portal
    case TenantUser = 'tenant_user';       // web "my" portal — owns tenants
    case User = 'user';                    // web/app "my" portal

    public function label(): string
    {
        return match ($this) {
            Role::Admin => 'Admin',
            Role::Core => 'Core',
            Role::Procurement => 'Procurement',
            Role::Workshop => 'Workshop',
            Role::DeliveryOrder => 'Delivery Order',
            Role::TenantUser => 'Tenant User',
            Role::User => 'End User',
        };
    }

    public function isAdmin(): bool
    {
        return $this === Role::Admin;
    }

    public function isCore(): bool
    {
        return $this === Role::Core;
    }

    public function isProcurement(): bool
    {
        return $this === Role::Procurement;
    }

    public function isWorkshop(): bool
    {
        return $this === Role::Workshop;
    }

    public function isDeliveryOrder(): bool
    {
        return $this === Role::DeliveryOrder;
    }

    public function isTenantUser(): bool
    {
        return $this === Role::TenantUser;
    }

    public function isUser(): bool
    {
        return $this === Role::User;
    }

    /** One of the web operations portals (Procurement / Workshop / Delivery Order). */
    public function isOperations(): bool
    {
        return in_array($this, [Role::Procurement, Role::Workshop, Role::DeliveryOrder], true);
    }

    /** Can sign in to the Filament admin (server-admin): Admin or Core. */
    public function canAccessFilament(): bool
    {
        return $this === Role::Admin || $this === Role::Core;
    }

    /** Only Admin may delete in Filament; Core has full read/write but no delete. */
    public function canDeleteInFilament(): bool
    {
        return $this === Role::Admin;
    }

    /** @deprecated use canAccessFilament() */
    public function isCentralStaff(): bool
    {
        return $this->canAccessFilament();
    }

    /** @deprecated use canAccessFilament() */
    public function isAllowedForFilamentAdminPanel(): bool
    {
        return $this->canAccessFilament();
    }
}
