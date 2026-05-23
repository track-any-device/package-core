<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ComputeBoard extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'name',
        'manufacturer',
        'mcu',
        'flash_kb',
        'ram_kb',
        'operating_voltage',
        'datasheet_url',
        'notes',
    ];

    protected $casts = [
        'flash_kb' => 'integer',
        'ram_kb' => 'integer',
        'operating_voltage' => 'decimal:2',
    ];

    public function deviceTypes(): BelongsToMany
    {
        return $this->belongsToMany(DeviceType::class, 'device_type_compute_board');
    }

    public function products(): MorphMany
    {
        return $this->morphMany(Product::class, 'productable');
    }
}
