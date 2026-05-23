<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\WorkflowTriggerType;
use TrackAnyDevice\Core\Database\Factories\WorkflowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Workflows live centrally. Ownership mirrors beats: exactly one of
 * tenant_id or user_id is set. Tenant-scoped workflows are visible to
 * any operator in that tenant; user-scoped workflows belong to the
 * end-user who created them.
 *
 * The graph column stores the React Flow node/edge JSON the visual
 * designer emits. The WorkflowExecutor walks that graph at run time.
 */
#[Fillable([
    'tenant_id',
    'user_id',
    'created_by',
    'name',
    'description',
    'trigger_type',
    'trigger_config',
    'graph',
    'is_enabled',
    'version',
    'last_run_at',
])]
class Workflow extends Model
{
    /** @use HasFactory<WorkflowFactory> */
    use HasFactory, UsesCentralConnection;

    protected function casts(): array
    {
        return [
            'trigger_type' => WorkflowTriggerType::class,
            'trigger_config' => 'array',
            'graph' => 'array',
            'is_enabled' => 'boolean',
            'version' => 'integer',
            'last_run_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }

    public function isPersonal(): bool
    {
        return $this->user_id !== null && $this->tenant_id === null;
    }

    public function isTenantWorkflow(): bool
    {
        return $this->tenant_id !== null;
    }

    /**
     * Returns the trigger node from the stored graph, or null if missing.
     *
     * @return array<string, mixed>|null
     */
    public function triggerNode(): ?array
    {
        foreach ($this->graph['nodes'] ?? [] as $node) {
            if (($node['data']['action_type'] ?? null) === 'trigger') {
                return $node;
            }
        }

        return null;
    }
}
