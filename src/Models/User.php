<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\Role;
use TrackAnyDevice\Core\Enums\StaffDepartment;
use TrackAnyDevice\Core\Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name', 'email', 'password', 'role',
    'primary_contact', 'public_contact', 'share_email',
    'email_verified_at', 'phone_verified_at',
    'display_timezone',
    'browser_latitude', 'browser_longitude', 'location_consented_at',
    'last_ip_address', 'ip_country_code', 'mobile_country_id',
    'last_otp_validated_on',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable, UsesCentralConnection;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'last_otp_validated_on' => 'datetime',
            'role' => Role::class,
            'share_email' => 'boolean',
            'location_consented_at' => 'datetime',
            'browser_latitude' => 'decimal:7',
            'browser_longitude' => 'decimal:7',
        ];
    }

    public function mobileCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'mobile_country_id');
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function hasPhone(): bool
    {
        return ! empty($this->primary_contact);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isSupervisor(): bool
    {
        return $this->role === Role::Supervisor;
    }

    public function isStaff(): bool
    {
        return $this->role === Role::Staff;
    }

    public function isTenantUser(): bool
    {
        return $this->role === Role::TenantUser;
    }

    public function isUser(): bool
    {
        return $this->role === Role::User;
    }

    public function isCentralStaff(): bool
    {
        return $this->role?->isCentralStaff() ?? false;
    }

    /**
     * Tenants this user is a member of. Membership is stored in the central
     * tenant_users pivot — admin/staff are NOT in this pivot (their access
     * is role-based and spans all tenants).
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')->withTimestamps();
    }

    public function beats(): BelongsToMany
    {
        return $this->belongsToMany(Beat::class)->withPivot('beat_role');
    }

    /**
     * Devices the end-user is following / has registered (central-side).
     * Linked through the user_devices pivot table.
     */
    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'user_devices')
            ->withPivot(['relationship', 'registered_at'])
            ->withTimestamps();
    }

    /**
     * Orders this user placed through the central /store flow.
     */
    public function orders()
    {
        return $this->hasMany(DeviceOrder::class);
    }

    /** Devices owned directly by this user (devices.user_id). */
    public function ownedDevices()
    {
        return $this->hasMany(Device::class);
    }

    /** Resolve display timezone, falling back to UTC. */
    public function displayTimezone(): string
    {
        return $this->display_timezone ?: config('app.timezone_display', 'UTC');
    }

    public function staffDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'staff_department_user')
            ->withPivot(['department', 'is_workshop'])
            ->withTimestamps();
    }

    public function hasDepartment(StaffDepartment $department): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->staffDepartmentEntries()
            ->where('department', $department->value)
            ->exists();
    }

    public function isWorkshop(): bool
    {
        return $this->staffDepartmentEntries()
            ->where('department', StaffDepartment::Warehouse->value)
            ->where('is_workshop', true)
            ->exists();
    }

    public function staffDepartmentEntries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\TrackAnyDevice\Core\Models\StaffDepartmentUser::class);
    }

    public function assignedWarehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'staff_department_user')
            ->wherePivot('department', StaffDepartment::Warehouse->value)
            ->withPivot(['is_workshop'])
            ->withTimestamps();
    }

    public function getDepartmentList(): array
    {
        return $this->staffDepartmentEntries()
            ->pluck('department')
            ->map(fn (string $d) => StaffDepartment::from($d))
            ->all();
    }
}
