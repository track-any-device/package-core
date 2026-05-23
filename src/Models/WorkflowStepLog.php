<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\WorkflowActionType;
use TrackAnyDevice\Core\Enums\WorkflowRunStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workflow_run_id',
    'node_id',
    'action_type',
    'input',
    'output',
    'status',
    'duration_ms',
    'error',
    'executed_at',
])]
class WorkflowStepLog extends Model
{
    use UsesCentralConnection;

    protected function casts(): array
    {
        return [
            'action_type' => WorkflowActionType::class,
            'status' => WorkflowRunStatus::class,
            'input' => 'array',
            'output' => 'array',
            'duration_ms' => 'integer',
            'executed_at' => 'datetime',
        ];
    }

    public function workflowRun(): BelongsTo
    {
        return $this->belongsTo(WorkflowRun::class);
    }
}
