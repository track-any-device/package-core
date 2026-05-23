<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Database\Factories\DomainFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stancl\Tenancy\Database\Models\Domain as BaseDomain;

/**
 * Tenant-resolution domain. stancl/tenancy's InitializeTenancyByDomain
 * middleware looks this table up by the request hostname.
 *
 * Lives in the central database (the only database — tenancy is enforced
 * at the query layer, not via per-tenant databases).
 */
class Domain extends BaseDomain
{
    /** @use HasFactory<DomainFactory> */
    use HasFactory, UsesCentralConnection;

    protected $fillable = [
        'tenant_id',
        'domain',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }
}
