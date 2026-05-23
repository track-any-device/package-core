<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant-customisable incident priority.
 *
 * Rows with tenant_id = NULL are platform defaults; once a tenant
 * defines any row, those defaults are hidden for that tenant.
 *
 * Named *Option to avoid colliding with the legacy
 * TrackAnyDevice\Core\Enums\IncidentPriority enum, which still drives default
 * behaviour and central-side serialization.
 */
#[Fillable([
    'tenant_id',
    'key',
    'label',
    'color',
    'sort_order',
    'is_default',
])]
class IncidentPriorityOption extends Model
{
    use UsesCentralConnection;

    protected $table = 'incident_priorities';

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Return the priority set effective for a tenant — tenant-defined
     * rows when any exist, otherwise the platform defaults.
     *
     * @return Collection<int, self>
     */
    public static function effectiveFor(?int $tenantId): Collection
    {
        $hasTenantRows = $tenantId !== null
            && static::query()->where('tenant_id', $tenantId)->exists();

        return static::query()
            ->when(
                $hasTenantRows,
                fn (Builder $q) => $q->where('tenant_id', $tenantId),
                fn (Builder $q) => $q->whereNull('tenant_id'),
            )
            ->orderBy('sort_order')
            ->get();
    }
}
