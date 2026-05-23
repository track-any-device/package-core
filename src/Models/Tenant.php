<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Tenant model — the platform-side record for each organisation.
 *
 * Lives in the central database. All tenant-owned tables (devices, beats,
 * assignees, incidents, alert_rules, …) also live centrally with a
 * `tenant_id` column; isolation is enforced at the query layer via
 * TrackAnyDevice\Core\Scopes\TenantScope. There is no per-tenant database anymore.
 */
class Tenant extends BaseTenant
{
    use HasDomains;

    /** @use HasFactory<TenantFactory> */
    use HasFactory, UsesCentralConnection;

    /**
     * Bigint auto-incrementing primary key (the existing schema uses it).
     */
    public $incrementing = true;

    protected $keyType = 'int';

    /**
     * Real DB columns. stancl's virtual-column behaviour falls back to JSON
     * `data` for anything not listed here.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'type',
            'interface_mode',
            'app_name',
            'logo_path',
            'primary_color',
            'color_scheme',
            'google_maps_api_key',
            'status',
            'registration_enabled',
            'approved_at',
            'metadata',
            'created_at',
            'updated_at',
        ];
    }

    protected $fillable = [
        'name',
        'slug',
        'type',
        'interface_mode',
        'app_name',
        'logo_path',
        'primary_color',
        'color_scheme',
        'google_maps_api_key',
        'status',
        'registration_enabled',
        'approved_at',
        'metadata',
    ];

    public const INTERFACE_DEFAULT = 'default';

    public const INTERFACE_NO_ORG = 'no_org';

    public function isNoOrgMode(): bool
    {
        return ($this->interface_mode ?? self::INTERFACE_DEFAULT) === self::INTERFACE_NO_ORG;
    }

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'metadata' => 'array',
            'status' => TenantStatus::class,
            'registration_enabled' => 'boolean',
        ];
    }

    public function isApproved(): bool
    {
        return $this->status === TenantStatus::Approved;
    }

    public function allowsRegistration(): bool
    {
        return (bool) $this->registration_enabled;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::url($this->logo_path) : null;
    }

    /**
     * Tenant members (cross-tenant access pivot). Always uses the central DB.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')->withTimestamps();
    }

    /**
     * Devices owned by this tenant (devices stay central — see CLAUDE.md hybrid spec).
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function beats(): HasMany
    {
        return $this->hasMany(Beat::class);
    }

    public function assignees(): HasMany
    {
        return $this->hasMany(Assignee::class);
    }

    public function assigneeTypes(): HasMany
    {
        return $this->hasMany(AssigneeType::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(DeviceOrder::class);
    }

    public function screens(): HasMany
    {
        return $this->hasMany(Screen::class)->orderBy('sort_order');
    }

    /**
     * The OAuthClient row paired with this tenant. Auto-created by
     * TenantObserver when the Tenant row is created.
     */
    public function oauthClient(): HasOne
    {
        return $this->hasOne('TrackAnyDevice\SsoServer\Models\OAuthClient');
    }
}
