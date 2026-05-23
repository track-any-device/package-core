<?php

namespace TrackAnyDevice\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hard-aborts with 404 when a "central" route is requested outside the
 * central host pool. Used by marketing / docs / CMS routes that should
 * NOT resolve on tenant subdomains.
 *
 * The check is purely host-based: if the request hostname is not in
 * config('tenancy.central_domains'), the route is invisible.
 *
 * ADMIN_DOMAIN, MY_DOMAIN, and LOGIN_DOMAIN are all intentionally
 * excluded from "central" here even though all three hosts are listed
 * in tenancy.central_domains for the tenancy resolver. Each is a
 * dedicated surface — marketing pages, docs, CMS, and the home route
 * must not bleed onto them. The Filament panel uses the tighter
 * 'admin.domain' middleware, the end-user app uses 'my.domain', and
 * the identity provider uses 'login.domain'.
 */
class EnsureCentralDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $centralDomains = (array) config('tenancy.central_domains', []);

        if (! in_array($host, $centralDomains, true)) {
            abort(404);
        }

        $adminDomain = (string) env('ADMIN_DOMAIN', '');
        if ($adminDomain !== '' && $host === $adminDomain) {
            abort(404);
        }

        $myDomain = (string) env('MY_DOMAIN', '');
        if ($myDomain !== '' && $host === $myDomain) {
            abort(404);
        }

        $loginDomain = (string) env('LOGIN_DOMAIN', '');
        if ($loginDomain !== '' && $host === $loginDomain) {
            abort(404);
        }

        return $next($request);
    }
}
