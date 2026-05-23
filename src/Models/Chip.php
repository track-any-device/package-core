<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\ChipType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Chip extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'name',
        'manufacturer',
        'type',
        'datasheet_url',
        'specifications',
        'notes',
    ];

    protected $casts = [
        'type' => ChipType::class,
        'specifications' => 'array',
    ];

    public function sensors(): BelongsToMany
    {
        return $this->belongsToMany(Sensor::class, 'chip_sensor');
    }

    public function deviceTypes(): BelongsToMany
    {
        return $this->belongsToMany(DeviceType::class, 'device_type_chip');
    }

    public function products(): MorphMany
    {
        return $this->morphMany(Product::class, 'productable');
    }
}
