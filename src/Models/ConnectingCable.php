<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\CableProtocol;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ConnectingCable extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'name',
        'connector_type',
        'protocol',
        'baud_rates',
        'notes',
    ];

    protected $casts = [
        'protocol' => CableProtocol::class,
        'baud_rates' => 'array',
    ];

    public function deviceTypes(): BelongsToMany
    {
        return $this->belongsToMany(DeviceType::class, 'device_type_connecting_cable');
    }

    public function products(): MorphMany
    {
        return $this->morphMany(Product::class, 'productable');
    }
}
