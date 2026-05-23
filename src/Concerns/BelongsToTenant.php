<?php

namespace TrackAnyDevice\Core\Concerns;

use TrackAnyDevice\Core\Models\Tenant;
use TrackAnyDevice\Core\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Applies automatic tenant scoping and tenant_id auto-population on create.
 *
 * For CENTRAL tables that carry a tenant_id column and are owned by exactly
 * one tenant at a time (Beat, Assignee, AssigneeType, Incident). Use
 * together with UsesCentralConnection — the global scope filters by
 * tenant_id, but the model itself must still live on the central connection.
 *
 * AlertRule deliberately does NOT use this trait — its rows with
 * `tenant_id = null` are global defaults visible to every tenant, which the
 * exact-match scope here would hide. Application code merges defaults with
 * tenant overrides when needed. Signals live in InfluxDB and are tag-scoped.
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (self $model) {
            if (empty($model->tenant_id) && function_exists('tenancy') && tenancy()->tenant) {
                $model->tenant_id = tenancy()->tenant->getKey();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
