<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\DeviceAssignmentStatus;
use TrackAnyDevice\Core\Database\Factories\DeviceAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'device_id',
    'assignee_id',
    'assigned_by',
    'assigned_at',
    'returned_at',
    'condition_out',
    'condition_in',
    'status',
    'notes',
])]
class DeviceAssignment extends Model
{
    /** @use HasFactory<DeviceAssignmentFactory> */
    use HasFactory, UsesCentralConnection;

    protected function casts(): array
    {
        return [
            'status' => DeviceAssignmentStatus::class,
            'assigned_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Assignee::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isActive(): bool
    {
        return $this->status === DeviceAssignmentStatus::Active;
    }
}
