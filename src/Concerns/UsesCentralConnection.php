<?php

namespace TrackAnyDevice\Core\Concerns;

/**
 * Pins an Eloquent model to the central database connection.
 *
 * The application now runs on a single central database — there is no
 * per-tenant DB swap. This trait remains in place so future reintroductions
 * of database tenancy (e.g. for high-volume telemetry) don't require
 * touching every model again. For now, it just resolves to the default
 * connection.
 */
trait UsesCentralConnection
{
    public function getConnectionName(): string
    {
        return config('tenancy.database.central_connection', 'mysql');
    }
}
