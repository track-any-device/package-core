<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\SensorDataType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sensor extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'name',
        'label',
        'slug',
        'unit',
        'data_type',
        'protocol',
        'description',
        'icon',
        'sort_order',
    ];

    protected $casts = [
        'data_type' => SensorDataType::class,
        'sort_order' => 'integer',
    ];

    public function deviceTypes(): BelongsToMany
    {
        return $this->belongsToMany(DeviceType::class)->withPivot('is_primary');
    }

    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class);
    }

    public function chips(): BelongsToMany
    {
        return $this->belongsToMany(Chip::class, 'chip_sensor');
    }

    public function displayLabel(): string
    {
        return $this->label ?: $this->name;
    }
}
