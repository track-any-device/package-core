<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\StreamChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'name',
        'class',
        'stream_channel',
        'supports_gsm_commands',
        'supports_stream',
        'version',
        'notes',
    ];

    protected $casts = [
        'stream_channel' => StreamChannel::class,
        'supports_gsm_commands' => 'boolean',
        'supports_stream' => 'boolean',
    ];

    public function deviceTypes(): HasMany
    {
        return $this->hasMany(DeviceType::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
