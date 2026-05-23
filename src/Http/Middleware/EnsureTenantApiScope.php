<?php

namespace TrackAnyDevice\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the bearer Sanctum token has the required ability for a
 * tenant API route.
 *
 * Tenant API tokens are minted in /settings/access-tokens with an
 * abilities list. Routes declare the ability they require via the
 * middleware parameter:
 *
 *   ->middleware('tenant.scope:devices.read')
 *
 * Falls back to 403 when the token lacks the ability.
 */
class EnsureTenantApiScope
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token) {
            abort(401, 'Sanctum token required');
        }

        // A wildcard '*' ability grants every scope — useful for admin
        // tokens or for backwards compatibility with the existing
        // 'mcp:use' tokens which were minted before scoped abilities.
        $abilities = $token->abilities ?? [];
        if (in_array('*', $abilities, true)) {
            return $next($request);
        }

        if (! in_array($ability, $abilities, true)) {
            abort(403, "Token missing required scope: {$ability}");
        }

        return $next($request);
    }
}
