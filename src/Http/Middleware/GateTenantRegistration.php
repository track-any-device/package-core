<?php

namespace TrackAnyDevice\Core\Http\Middleware;

use TrackAnyDevice\Core\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate the /register endpoints on tenant subdomains.
 *
 * Each Tenant has a `registration_enabled` boolean. When false (the
 * default), public signup on that tenant's subdomain is disabled and
 * any GET/POST to /register returns 404.
 *
 * Central-host requests fall straight through — the global Fortify
 * registration page is controlled by `fortify.features` instead.
 *
 * Runs in the global web stack AFTER InitializeTenancyForRequest so
 * `tenancy()->tenant` is bound by the time we check.
 */
class GateTenantRegistration
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! function_exists('tenancy') || ! tenancy()->tenant) {
            return $next($request);
        }

        $path = trim($request->path(), '/');
        if ($path !== 'register') {
            return $next($request);
        }

        $tenant = Tenant::find(tenancy()->tenant->getKey());

        if (! $tenant || ! $tenant->allowsRegistration()) {
            abort(404);
        }

        return $next($request);
    }
}
