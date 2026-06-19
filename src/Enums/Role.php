<?php

namespace TrackAnyDevice\Core\Enums;

/**
 * Single-Role permission model (restructure 2026-06-19, Workstream F).
 *
 * Target roles: Admin, Core, Procurement, Workshop, DeliveryOrder, TenantUser, User.
 * Supervisor + Staff are DEPRECATED — retained only so existing code/data keep working during the
 * migration; the role+department→role data migration maps them (and the old StaffDepartments) to
 * Core, after which they can be removed. See docs/audit/RESTRUCTURE-PLAN.md.
 *
 * NOTE: additive/non-breaking on this branch — adds cases + helpers, retains old ones. Removing
 * Supervisor/Staff later is a breaking change (minor bump + changelog) once package-admin no longer
 * references StaffDepartment.
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

    /** @deprecated map to Core via migration, then remove */
    case Supervisor = 'supervisor';
    /** @deprecated map to Core via migration, then remove */
    case Staff = 'staff';

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
            Role::Supervisor => 'Supervisor (legacy)',
            Role::Staff => 'Staff (legacy)',
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

    /** Can sign in to the Filament admin (server-admin): Admin or Core (+ legacy staff during migration). */
    public function canAccessFilament(): bool
    {
        return in_array($this, [Role::Admin, Role::Core, Role::Supervisor, Role::Staff], true);
    }

    /** Only Admin may delete in Filament; Core has full read/write but no delete. */
    public function canDeleteInFilament(): bool
    {
        return $this === Role::Admin;
    }

    // ---- deprecated shims: keep existing callers compiling during the migration ----

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

    /** @deprecated folds into Core */
    public function isSupervisor(): bool
    {
        return $this === Role::Supervisor;
    }

    /** @deprecated folds into Core */
    public function isStaff(): bool
    {
        return $this === Role::Staff;
    }
}
