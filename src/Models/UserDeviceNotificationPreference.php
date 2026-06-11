<?php

namespace TrackAnyDevice\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TrackAnyDevice\Core\Concerns\UsesCentralConnection;

class UserDeviceNotificationPreference extends Model
{
    use UsesCentralConnection;

    protected $table = 'user_device_notification_preferences';

    protected $fillable = [
        'user_id',
        'device_id',
        'event_type',
        'sms_enabled',
        'sms_disabled_until',
    ];

    protected function casts(): array
    {
        return [
            'sms_enabled'       => 'boolean',
            'sms_disabled_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /** Returns true if SMS is currently active (not snoozed). */
    public function isSmsActive(): bool
    {
        if (! $this->sms_enabled) {
            return false;
        }
        if ($this->sms_disabled_until && $this->sms_disabled_until->isFuture()) {
            return false;
        }

        return true;
    }
}
