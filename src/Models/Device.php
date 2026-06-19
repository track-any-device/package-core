<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\DeviceStatus;
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
    'device_type_id',
    'imei',
    'broadcast_id',
    'sim_number',
    'gsm_number',
    'apn_settings',
    'password',
    'name',
    'image',
    'status',
    'battery_level',
    'last_lat',
    'last_lon',
    'last_seen_at',
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
            'apn_settings' => 'array',
            'battery_level' => 'integer',
            'last_lat' => 'decimal:7',
            'last_lon' => 'decimal:7',
            'last_seen_at' => 'datetime',
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

    /** Resolved map icon — comes from the device type (per-device map_icon override was removed). */
    public function effectiveMapIcon(): ?string
    {
        return $this->deviceType?->map_icon;
    }

    /** Resolved driver class — resolved from the device type's originalModel (no per-device driver_id). */
    public function effectiveDriverClass(): ?string
    {
        return $this->deviceType?->effectiveDriverClass();
    }

    /**
     * Reconcile DeviceStatus from ownership (slim model: active | blocked | pending).
     * `blocked` is set explicitly by an admin and is never produced here.
     */
    public function reconciledStatus(): DeviceStatus
    {
        $hasOwner = ! empty($this->tenant_id) || ! empty($this->user_id);

        return $hasOwner ? DeviceStatus::Active : DeviceStatus::Pending;
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
