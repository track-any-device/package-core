<?php

namespace TrackAnyDevice\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hard-aborts with 404 when an end-user app route is requested anywhere
 * other than the dedicated my.* host (env MY_DOMAIN). After the host move
 * the end-user app lives at my.{APP_DOMAIN}; the bare central host and
 * every tenant subdomain must 404 on /orders, /devices, /tenants,
 * /incidents, /beats.
 *
 * Falls back to also accepting the bare central host (APP_DOMAIN) so the
 * end-user app is still reachable locally during the transition when
 * MY_DOMAIN is not yet set in env. Once MY_DOMAIN is configured the
 * fallback path becomes irrelevant.
 */
class EnsureMyDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $myDomain = (string) env('MY_DOMAIN', '');

        // Primary case: explicit MY_DOMAIN configured → must match exactly.
        if ($myDomain !== '') {
            if ($host !== $myDomain) {
                abort(404);
            }

            return $next($request);
        }

        // Transitional fallback for environments that have not yet set
        // MY_DOMAIN — accept any central_domains entry so the end-user
        // app still works locally / in tests against the bare central host.
        $centralDomains = (array) config('tenancy.central_domains', []);
        if (! in_array($host, $centralDomains, true)) {
            abort(404);
        }

        return $next($request);
    }
}
