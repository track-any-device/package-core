<?php

namespace TrackAnyDevice\Core\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Supervisor = 'supervisor';
    case Staff = 'staff';
    case TenantUser = 'tenant_user';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            Role::Admin => 'Admin',
            Role::Supervisor => 'Supervisor',
            Role::Staff => 'Staff',
            Role::TenantUser => 'Tenant User',
            Role::User => 'End User',
        };
    }

    public function isAdmin(): bool
    {
        return $this === Role::Admin;
    }

    public function isSupervisor(): bool
    {
        return $this === Role::Supervisor;
    }

    public function isStaff(): bool
    {
        return $this === Role::Staff;
    }

    public function isTenantUser(): bool
    {
        return $this === Role::TenantUser;
    }

    public function isUser(): bool
    {
        return $this === Role::User;
    }

    public function isCentralStaff(): bool
    {
        return $this === Role::Admin || $this === Role::Supervisor || $this === Role::Staff;
    }

    public function isAllowedForFilamentAdminPanel(): bool
    {
        return $this->isCentralStaff();
    }
}
