<?php

namespace TrackAnyDevice\Core\Http\Middleware;

use TrackAnyDevice\SsoServer\Models\OAuthClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate tenant-domain requests by tenant_users pivot membership.
 *
 * Runs AFTER InitializeTenancyByDomain + CheckTenantApproved. Each tenant
 * subdomain has its own session — there is no shared cookie domain. An
 * unauthenticated visitor is bounced through the OAuth flow:
 *
 *   1. tenant.example.com/<path> → redirect to
 *      central/oauth/authorize?client_id=<tenant_client>&redirect_uri=<callback>&state=<nonce>
 *   2. central authenticates the user (or skips if already signed in there)
 *   3. central mints a one-time SSO token + redirects to the tenant's
 *      /sso/callback?token=...&state=... endpoint
 *   4. tenant's SsoCallbackController consumes the token, calls Auth::login,
 *      and the user lands on their original target on the tenant subdomain.
 *
 * - Unauthenticated         → bounce through OAuth as above
 * - admin/supervisor/staff  → allow unconditionally (central staff)
 * - tenant_user             → must be in the central tenant_users pivot
 *
 * /register and /sso/callback are exempt from the OAuth bounce — they are
 * the entry points by which an unauthenticated visitor establishes a
 * session on the tenant in the first place.
 */
class AuthorizeTenantAccess
{
    /**
     * Paths that may be reached on a tenant subdomain without an active
     * tenant session. Each is gated by its own controller-level guard:
     *   - /register      → GateTenantRegistration middleware
     *   - /sso/callback  → token validation in SsoCallbackController
     */
    private const UNAUTHENTICATED_ALLOWLIST = ['register', 'sso/callback'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! function_exists('tenancy') || ! tenancy()->tenant) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            if (in_array(trim($request->path(), '/'), self::UNAUTHENTICATED_ALLOWLIST, true)) {
                return $next($request);
            }

            return $this->redirectThroughOAuth($request);
        }

        if ($user->role?->isCentralStaff()) {
            return $next($request);
        }

        $belongs = DB::connection(config('tenancy.database.central_connection'))
            ->table('tenant_users')
            ->where('user_id', $user->id)
            ->where('tenant_id', tenancy()->tenant->getKey())
            ->exists();

        if (! $belongs) {
            abort(403, 'You do not have access to this tenant.');
        }

        return $next($request);
    }

    /**
     * Build the OAuth authorize URL for this tenant's paired OAuthClient
     * and redirect the visitor there. Falls back to a generic 404 when
     * the tenant has no oauth_client row (defensive — TenantObserver
     * provisions one on tenant creation, so this only fires for tenants
     * created before PR #93).
     */
    private function redirectThroughOAuth(Request $request): Response
    {
        $client = OAuthClient::query()
            ->where('tenant_id', tenancy()->tenant->getKey())
            ->where('is_active', true)
            ->first();

        if (! $client) {
            abort(500, 'This tenant has not been provisioned for SSO. Contact support.');
        }

        $redirectUri = $this->resolveCallbackUri($request, $client);

        // /oauth/authorize lives on login.{APP_DOMAIN} (the dedicated
        // identity host). Fall back to the bare central host when
        // LOGIN_DOMAIN is unset (legacy single-host deploys); the route
        // file's login.domain middleware accepts central in that mode.
        $loginHost = config('app.login_domain') ?: config('app.domain', 'localhost');

        $authorizeUrl = $request->getScheme().'://'.$loginHost.'/oauth/authorize?'.http_build_query([
            'client_id' => $client->client_id,
            'redirect_uri' => $redirectUri,
            'state' => Str::random(40),
        ]);

        return redirect()->away($authorizeUrl);
    }

    /**
     * Pick the SSO callback URL we'll ask login.* to redirect back to.
     * Prefer an entry on the client's redirect_uris allow-list that
     * matches the current request host so the user lands back exactly
     * where they came from; otherwise fall back to the first allow-listed
     * URI.
     */
    private function resolveCallbackUri(Request $request, OAuthClient $client): string
    {
        $allowed = is_array($client->redirect_uris) ? $client->redirect_uris : [];
        $host = $request->getHost();

        foreach ($allowed as $uri) {
            if (parse_url((string) $uri, PHP_URL_HOST) === $host) {
                return (string) $uri;
            }
        }

        return (string) ($allowed[0] ?? $request->getScheme().'://'.$host.'/sso/callback');
    }
}
