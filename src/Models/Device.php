<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\DeviceStatus;
use TrackAnyDevice\Core\Enums\OnboardingStatus;
use TrackAnyDevice\Core\Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'tenant_id',
    'user_id',
    'assigned_at',
    'device_type_id',
    'driver_id',
    'imei',
    'serial_number',
    'sim_number',
    'gsm_number',
    'gsm_network_id',
    'iccid',
    'firmware_version',
    'password',
    'name',
    'map_icon',
    'status',
    'onboarding_status',
    'is_approved',
    'is_visible_to_tenant',
    'battery_level',
    'last_lat',
    'last_lon',
    'last_seen_at',
    'last_signal_at',
    'last_update_requested_at',
    'connection_attempt_count',
    'next_connection_attempt_at',
    'metadata',
    'notes',
])]
class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use HasFactory, SoftDeletes, UsesCentralConnection;

    protected function casts(): array
    {
        return [
            'status' => DeviceStatus::class,
            'onboarding_status' => OnboardingStatus::class,
            'is_approved' => 'boolean',
            'is_visible_to_tenant' => 'boolean',
            'battery_level' => 'integer',
            'last_lat' => 'decimal:7',
            'last_lon' => 'decimal:7',
            'last_seen_at' => 'datetime',
            'last_signal_at' => 'datetime',
            'last_update_requested_at' => 'datetime',
            'next_connection_attempt_at' => 'datetime',
            'connection_attempt_count' => 'integer',
            'assigned_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function gsmNetwork(): BelongsTo
    {
        return $this->belongsTo(GsmNetwork::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(DeviceCommand::class);
    }

    public function deviceAssignments(): HasMany
    {
        return $this->hasMany(DeviceAssignment::class);
    }

    public function beatAssignments(): HasMany
    {
        return $this->hasMany(BeatAssignment::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(DeviceOrder::class);
    }

    public function sensors(): BelongsToMany
    {
        return $this->belongsToMany(Sensor::class)->orderBy('sort_order');
    }

    /** Returns the set of sensor slugs enabled on this device (falls back to device type defaults). */
    public function effectiveSensorSlugs(): array
    {
        $direct = $this->sensors->pluck('slug')->all();
        if (! empty($direct)) {
            return $direct;
        }

        return $this->deviceType?->sensors->pluck('slug')->all() ?? [];
    }

    /** Resolved map icon — per-device override or device type's map_icon. */
    public function effectiveMapIcon(): ?string
    {
        return $this->map_icon ?? $this->deviceType?->map_icon;
    }

    /** Resolved driver class — per-device override or device type's driver. */
    public function effectiveDriverClass(): ?string
    {
        return $this->driver?->class ?? $this->deviceType?->effectiveDriverClass();
    }

    /**
     * Reconcile DeviceStatus from ownership + SIM state.
     *
     *   No GSM + no tenant/user → warehouse
     *   GSM + no tenant/user    → available
     *   Has tenant or user      → assigned
     */
    public function reconciledStatus(): DeviceStatus
    {
        $hasGsm = ! empty($this->gsm_number);
        $hasOwner = ! empty($this->tenant_id) || ! empty($this->user_id);

        return match (true) {
            $hasOwner => DeviceStatus::Assigned,
            $hasGsm => DeviceStatus::Available,
            default => DeviceStatus::Warehouse,
        };
    }

    public function isStock(): bool
    {
        return $this->tenant_id === null && $this->user_id === null;
    }

    public function activeDeviceAssignment(): HasMany
    {
        return $this->hasMany(DeviceAssignment::class)->whereNull('returned_at');
    }

    public function activeBeatAssignment(): HasMany
    {
        return $this->hasMany(BeatAssignment::class)->whereNull('effective_to');
    }
}
