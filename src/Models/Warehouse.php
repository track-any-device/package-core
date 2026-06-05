<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'code',
    'address',
    'city',
    'country_id',
    'is_active',
])]
class Warehouse extends Model
{
    use UsesCentralConnection;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WarehouseLog::class);
    }
}
