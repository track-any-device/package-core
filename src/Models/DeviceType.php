<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\StreamChannel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DeviceType extends Model
{
    use HasFactory, UsesCentralConnection;

    protected $fillable = [
        'name',
        'slug',
        'original_model',
        'driver_class',
        'driver_id',
        'description',
        'image',
        'map_icon',
        'images',
        'manual_url',
        'protocol_url',
        'default_password',
        'stream_channel',
        'meta',
        'is_active',
        'is_featured',
        'quantity',
        'price_usd',
        'price_pkr',
        'min_quantity',
        'quantity_multiple',
        'max_quantity',
        'bulk_quantity',
        'badge_label',
        'badge_color',
        'configuration_schema',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'images' => 'array',
        'meta' => 'array',
        'stream_channel' => StreamChannel::class,
        'quantity' => 'integer',
        'price_usd' => 'decimal:2',
        'price_pkr' => 'decimal:2',
        'min_quantity' => 'integer',
        'quantity_multiple' => 'integer',
        'max_quantity' => 'integer',
        'bulk_quantity' => 'integer',
        'configuration_schema' => 'array',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_featured', true);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function sections(): MorphMany
    {
        return $this->morphMany(PageSection::class, 'sectionable')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    public function allSections(): MorphMany
    {
        return $this->morphMany(PageSection::class, 'sectionable')->orderBy('sort_order');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function sensors(): BelongsToMany
    {
        return $this->belongsToMany(Sensor::class)
            ->withPivot('is_primary')
            ->orderBy('sort_order');
    }

    public function chips(): BelongsToMany
    {
        return $this->belongsToMany(Chip::class, 'device_type_chip');
    }

    public function computeBoards(): BelongsToMany
    {
        return $this->belongsToMany(ComputeBoard::class, 'device_type_compute_board');
    }

    public function connectingCables(): BelongsToMany
    {
        return $this->belongsToMany(ConnectingCable::class, 'device_type_connecting_cable');
    }

    public function chargingSets(): BelongsToMany
    {
        return $this->belongsToMany(ChargingSet::class, 'device_type_charging_set');
    }

    public function products(): MorphMany
    {
        return $this->morphMany(Product::class, 'productable');
    }

    /** Resolved driver class — per-device override or device type's driver. */
    public function effectiveDriverClass(): ?string
    {
        return $this->driver?->class ?? $this->driver_class;
    }
}
