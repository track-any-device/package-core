<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\BelongsToTenant;
use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\AssigneeStatus;
use TrackAnyDevice\Core\Database\Factories\AssigneeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tenant_id', 'assignee_type_id', 'name', 'code', 'status', 'metadata', 'notes'])]
class Assignee extends Model
{
    /** @use HasFactory<AssigneeFactory> */
    use BelongsToTenant, HasFactory, UsesCentralConnection;

    protected function casts(): array
    {
        return [
            'status' => AssigneeStatus::class,
            'metadata' => 'array',
        ];
    }

    public function assigneeType(): BelongsTo
    {
        return $this->belongsTo(AssigneeType::class);
    }

    public function deviceAssignments(): HasMany
    {
        return $this->hasMany(DeviceAssignment::class);
    }

    public function activeDeviceAssignment(): HasMany
    {
        return $this->hasMany(DeviceAssignment::class)->whereNull('returned_at');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
}
