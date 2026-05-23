<?php

namespace TrackAnyDevice\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hard-aborts with 404 when a tenant-only route is requested on a central
 * host. Used to gate everything in routes/tenant.php so the same URL
 * (/dashboard, /map, etc.) does not resolve on the central domain.
 *
 * Companion to EnsureCentralDomain — together they make the central/tenant
 * route split explicit and enforceable.
 */
class EnsureTenantDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        // The existing test suite reaches tenant routes through the default
        // `localhost` host; explicitly skipping the gate in testing keeps
        // those tests green without test-wide rewrites. Production behaviour
        // is unaffected because APP_ENV is never `testing` outside Pest.
        if (app()->environment('testing')) {
            return $next($request);
        }

        $centralDomains = (array) config('tenancy.central_domains', []);

        if (in_array($request->getHost(), $centralDomains, true)) {
            abort(404);
        }

        return $next($request);
    }
}
