<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ChargingSet extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'name',
        'connector',
        'voltage',
        'current_ma',
        'wireless',
        'notes',
    ];

    protected $casts = [
        'voltage' => 'decimal:2',
        'current_ma' => 'integer',
        'wireless' => 'boolean',
    ];

    public function deviceTypes(): BelongsToMany
    {
        return $this->belongsToMany(DeviceType::class, 'device_type_charging_set');
    }

    public function products(): MorphMany
    {
        return $this->morphMany(Product::class, 'productable');
    }
}
