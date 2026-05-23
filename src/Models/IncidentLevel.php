<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant-customisable incident escalation level.
 *
 * level_number is the integer rank used by the violation detector:
 *   1  = device left its assigned (leaf) beat
 *   2  = device left the parent beat
 *   3+ = device left successive ancestor beats
 *
 * Tenants name and colour each level (e.g. 1=Local, 2=District,
 * 3=Regional). Rows with tenant_id = NULL are platform defaults.
 */
#[Fillable([
    'tenant_id',
    'level_number',
    'label',
    'color',
    'description',
])]
class IncidentLevel extends Model
{
    use UsesCentralConnection;

    protected function casts(): array
    {
        return [
            'level_number' => 'integer',
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
            ->orderBy('level_number')
            ->get();
    }
}
