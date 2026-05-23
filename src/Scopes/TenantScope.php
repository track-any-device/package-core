<?php

namespace TrackAnyDevice\Core\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that restricts queries on central tenant-scoped tables to the
 * currently initialized tenant.
 *
 * Applies to central tables that carry a `tenant_id` column AND are owned
 * by exactly one tenant at a time (Beat, Assignee, AssigneeType). The trait
 * BelongsToTenant on those models attaches this scope automatically.
 *
 * - Outside any tenant context (central admin / Filament): the scope is a
 *   no-op so admin queries see every tenant's rows.
 * - Inside a tenant context (a request resolved by stancl's middleware):
 *   the scope filters by tenancy()->tenant->id so the tenant only sees
 *   their own rows.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = function_exists('tenancy') ? tenancy()->tenant : null;

        if (! $tenant) {
            return;
        }

        $builder->where($model->getTable().'.tenant_id', $tenant->getKey());
    }

    /**
     * Allow controllers to remove this scope when serving personal
     * (user_id-owned) rows on the central host. Use with care — never
     * call from inside a tenant request.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenantScope', fn (Builder $b) => $b->withoutGlobalScope(self::class));
    }
}
