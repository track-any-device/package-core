<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Pinned to the central database connection so Sanctum can find tokens
 * during tenant requests. Inside a tenant request stancl/tenancy switches
 * the default connection to the tenant DB — without this pin, Sanctum
 * would look for personal_access_tokens in the wrong database and fail
 * every Bearer auth attempt on a tenant subdomain.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use UsesCentralConnection;
}
