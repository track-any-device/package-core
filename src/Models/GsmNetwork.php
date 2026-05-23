<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GsmNetwork extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'name',
        'country_code',
        'country_id',
        'apn',
        'apn_username',
        'apn_password',
        'meta',
        'is_active',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
