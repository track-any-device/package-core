<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant-customisable incident status.
 *
 * Every tenant set MUST contain exactly one is_open=true row and one
 * is_closed=true row. These two are global semantic anchors — tenants
 * can rename and re-style them but cannot remove them. Tenants are
 * free to insert additional intermediate statuses (e.g.
 * "acknowledged", "in_review") between open and closed.
 *
 * Named *Option to avoid colliding with the legacy
 * TrackAnyDevice\Core\Enums\IncidentStatus enum.
 */
#[Fillable([
    'tenant_id',
    'key',
    'label',
    'color',
    'sort_order',
    'is_open',
    'is_closed',
])]
class IncidentStatusOption extends Model
{
    use UsesCentralConnection;

    protected $table = 'incident_statuses';

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_open' => 'boolean',
            'is_closed' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
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

    /**
     * Return whether a status key counts as "closed" for the given
     * tenant. Used by the central /my-incidents serializer to collapse
     * tenant-side statuses to a binary open / closed surface.
     */
    public static function isClosedKey(?int $tenantId, string $key): bool
    {
        $row = static::effectiveFor($tenantId)->firstWhere('key', $key);

        return (bool) $row?->is_closed;
    }
}
