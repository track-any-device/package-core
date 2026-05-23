<?php

namespace TrackAnyDevice\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hard-aborts with 404 when an identity-provider route is requested
 * anywhere other than the dedicated login host (env LOGIN_DOMAIN).
 *
 * Fortify's auth routes (/login, /register, /forgot-password, etc.) and
 * the /oauth/authorize endpoint all live on this host. Every other
 * surface (central / admin / my / tenant) bounces unauthed visitors
 * here.
 *
 * Falls back to also accepting the bare central host (APP_DOMAIN) so
 * the identity provider remains reachable locally during the
 * transition when LOGIN_DOMAIN is not yet set in env. Once LOGIN_DOMAIN
 * is configured the fallback path becomes irrelevant.
 */
class EnsureLoginDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $loginDomain = (string) env('LOGIN_DOMAIN', '');

        if ($loginDomain !== '') {
            if ($host !== $loginDomain) {
                abort(404);
            }

            return $next($request);
        }

        // Transitional fallback for environments that have not yet set
        // LOGIN_DOMAIN — accept any central_domains entry so Fortify
        // routes still work locally / in tests against the bare central
        // host.
        $centralDomains = (array) config('tenancy.central_domains', []);
        if (! in_array($host, $centralDomains, true)) {
            abort(404);
        }

        return $next($request);
    }
}
