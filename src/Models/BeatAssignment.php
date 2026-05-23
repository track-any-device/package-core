<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\BeatAssignmentStatus;
use TrackAnyDevice\Core\Database\Factories\BeatAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'device_id',
    'beat_id',
    'assigned_by',
    'effective_from',
    'effective_to',
    'status',
    'reason',
    'notes',
])]
class BeatAssignment extends Model
{
    /** @use HasFactory<BeatAssignmentFactory> */
    use HasFactory, UsesCentralConnection;

    protected function casts(): array
    {
        return [
            'status' => BeatAssignmentStatus::class,
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function beat(): BelongsTo
    {
        return $this->belongsTo(Beat::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isActive(): bool
    {
        return $this->status === BeatAssignmentStatus::Active;
    }
}
