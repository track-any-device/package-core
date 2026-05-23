<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\WorkflowRunStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workflow_id',
    'tenant_id',
    'incident_id',
    'triggered_by',
    'input_context',
    'status',
    'started_at',
    'completed_at',
    'duration_ms',
    'error',
])]
class WorkflowRun extends Model
{
    use UsesCentralConnection;

    protected function casts(): array
    {
        return [
            'input_context' => 'array',
            'status' => WorkflowRunStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_ms' => 'integer',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStepLog::class)->orderBy('executed_at');
    }
}
