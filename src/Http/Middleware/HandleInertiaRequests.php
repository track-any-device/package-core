<?php

namespace TrackAnyDevice\Core\Http\Middleware;

use TrackAnyDevice\Core\Enums\OAuthClientKind;
use TrackAnyDevice\Core\Enums\Role;
use TrackAnyDevice\Core\Models\Beat;
use TrackAnyDevice\Core\Models\Country;
use TrackAnyDevice\Core\Models\NavLink;
use TrackAnyDevice\SsoServer\Models\OAuthClient;
use TrackAnyDevice\Core\Support\ThemeResolver;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Return the blade view name for the current host.
     * Each surface has its own view with a hardcoded JS entry point.
     */
    public function rootView(Request $request): string
    {
        $host = $request->getHost();

        if ($host === (string) env('LOGIN_DOMAIN', '')) {
            return 'login';
        }

        if ($host === (string) env('MY_DOMAIN', '')) {
            return 'my';
        }

        $centralDomains = (array) config('tenancy.central_domains', []);
        if (in_array($host, $centralDomains, true)) {
            return 'web';
        }

        return 'tenant';
    }

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    private function isLocalBeatUser(Request $request): bool
    {
        $user = $request->user();
        if (! $user || $user->role === Role::Admin) {
            return false;
        }

        $userBeats = $user->beats()->pluck('beats.id');
        if ($userBeats->isEmpty()) {
            return false;
        }

        // A local beat user has beats where NONE of them have children
        return ! Beat::whereIn('id', $userBeats)->whereHas('children')->exists();
    }

    public function share(Request $request): array
    {
        $tenant = function_exists('tenancy') ? tenancy()->tenant : null;

        return [
            ...parent::share($request),
            'name' => $tenant?->app_name ?? config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            // Server is UTC; every timestamp in props is ISO-8601 with 'Z'.
            // The frontend uses this to render dates in the user's local zone.
            'user_timezone' => $request->user()?->displayTimezone()
                ?? $request->header('X-Browser-Timezone')
                ?? config('app.timezone_display', 'UTC'),
            // Countries available for the mobile-number picker — only those
            // with an SMS gateway configured. Empty array if the central
            // countries table hasn't been seeded yet.
            'phone_countries' => Country::active()
                ->withSmsGateway()
                ->orderBy('name')
                ->get(['id', 'iso_code', 'name', 'country_code'])
                ->toArray(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'unreadNotificationsCount' => $request->user()
                ? $request->user()->unreadNotifications()->count()
                : 0,
            // true when user only belongs to local (leaf) beats — hides dashboard
            'isLocalBeatUser' => $this->isLocalBeatUser($request),
            // Shared across all public (web) pages.
            //   nav_links       — top-bar links (legacy shape kept for SiteHeader)
            //   footer_links    — grouped footer columns keyed by placement
            //
            // Both are sourced from the central `nav_links` table, edited
            // through the Filament CMS → Nav Links resource.
            'nav_links' => NavLink::active()
                ->placement(NavLink::PLACEMENT_HEADER)
                ->get(['id', 'label', 'href', 'target'])
                ->toArray(),
            'footer_links' => [
                'quick' => NavLink::active()
                    ->placement(NavLink::PLACEMENT_FOOTER_QUICK)
                    ->get(['id', 'label', 'href', 'target'])
                    ->toArray(),
                'support' => NavLink::active()
                    ->placement(NavLink::PLACEMENT_FOOTER_SUPPORT)
                    ->get(['id', 'label', 'href', 'target'])
                    ->toArray(),
                'legal' => NavLink::active()
                    ->placement(NavLink::PLACEMENT_FOOTER_LEGAL)
                    ->get(['id', 'label', 'href', 'target'])
                    ->toArray(),
            ],
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'app_name' => $tenant->app_name ?? config('app.name'),
                'logo_url' => $tenant->logoUrl(),
                'primary_color' => $tenant->primary_color,
            ] : null,
            // Absolute URLs to the platform's other public surfaces, used
            // by chrome components for cross-host links. These MUST be
            // absolute and the rendered element MUST be a plain anchor
            // (not Inertia <Link>) — Inertia's XHR navigation cannot
            // follow a cross-origin response, so a relative '/admin'
            // would hit central's 301 redirect via XHR and trip CORS.
            'hosts' => [
                'central' => $request->getScheme().'://'.(config('app.domain') ?: $request->getHost()),
                'admin' => env('ADMIN_DOMAIN') ? $request->getScheme().'://'.env('ADMIN_DOMAIN') : null,
                'my' => env('MY_DOMAIN') ? $request->getScheme().'://'.env('MY_DOMAIN') : null,
            ],
            // Resolved theme metadata for the root layout + AppConfig JS.
            // Central host always returns scheme=default (enforced inside
            // ThemeResolver), so a malicious or stale tenant value cannot
            // bleed into the admin/Filament experience.
            'theme' => ThemeResolver::toArray(),
            // When the user lands on /login (or any auth page) via
            // /oauth/authorize, OAuthAuthorizeController stashes the
            // requesting client's id in session.sso.client_id. We expose
            // the tenant-branded subset of that client here so the auth
            // pages render the right logo / name / accent instead of the
            // platform defaults. NULL on the public site, when no client
            // is in flight, or for central / my clients (which always
            // present the platform identity).
            'clientContext' => $this->resolveClientContext($request),
        ];
    }

    /**
     * @return array{
     *   name: string,
     *   app_name: string,
     *   logo_url: ?string,
     *   primary_color: ?string,
     *   auth_layout: string,
     *   auth_background_url: ?string,
     *   auth_login_title: ?string,
     *   auth_login_description: ?string,
     *   registration_enabled: bool,
     *   auth_register_title: ?string,
     *   auth_register_description: ?string,
     *   auth_forgot_title: ?string,
     *   auth_forgot_description: ?string,
     * }|null
     */
    private function resolveClientContext(Request $request): ?array
    {
        $clientId = $request->session()->get('sso.client_id');
        if (! $clientId) {
            return null;
        }

        $client = OAuthClient::query()
            ->with('tenant')
            ->find($clientId);

        // Central / my clients use the platform identity — no per-tenant
        // skin. Only tenant-kind clients pass theming through to the
        // auth pages.
        if (! $client || $client->kind !== OAuthClientKind::Tenant || ! $client->tenant) {
            return null;
        }

        $tenant = $client->tenant;

        return [
            'name'                      => $tenant->name,
            'app_name'                  => $tenant->app_name ?? config('app.name'),
            'logo_url'                  => $tenant->logoUrl(),
            'primary_color'             => $tenant->primary_color,
            'auth_layout'               => $tenant->auth_layout ?? 'split',
            'auth_background_url'       => $tenant->authBackgroundUrl(),
            'auth_login_title'          => $tenant->auth_login_title,
            'auth_login_description'    => $tenant->auth_login_description,
            'registration_enabled'      => (bool) $tenant->registration_enabled,
            'auth_register_title'       => $tenant->auth_register_title,
            'auth_register_description' => $tenant->auth_register_description,
            'auth_forgot_title'         => $tenant->auth_forgot_title,
            'auth_forgot_description'   => $tenant->auth_forgot_description,
        ];
    }
}
