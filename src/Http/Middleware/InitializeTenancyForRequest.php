<?php

namespace TrackAnyDevice\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bootstrap stancl/tenancy for the current request, but only when the host
 * is NOT one of the central domains. Central-domain requests (admin panel,
 * login, public site, org-setup, etc.) pass through with the default DB
 * connection untouched.
 *
 * Mirrors the legacy IdentifyTenant entry point — so the same global web
 * middleware stack can serve both central and tenant routes without
 * splitting the route file.
 */
class InitializeTenancyForRequest
{
    public function __construct(protected InitializeTenancyByDomain $inner) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $centralDomains = (array) config('tenancy.central_domains', []);

        if (in_array($host, $centralDomains, true)) {
            return $next($request);
        }

        return $this->inner->handle($request, $next);
    }
}
