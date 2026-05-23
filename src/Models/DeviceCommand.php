<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\DeviceCommandStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'device_id',
    'command_type',
    'command_payload',
    'channel',
    'status',
    'requested_by',
    'sent_at',
    'response',
    'failed_reason',
])]
class DeviceCommand extends Model
{
    use HasFactory, UsesCentralConnection;

    protected function casts(): array
    {
        return [
            'status' => DeviceCommandStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isPending(): bool
    {
        return $this->status === DeviceCommandStatus::Pending;
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }
}
