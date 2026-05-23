<?php

namespace TrackAnyDevice\Core\Support;

use TrackAnyDevice\Core\Models\Tenant;
use Illuminate\Support\Facades\Storage;

/**
 * Resolves per-request branding (color scheme, app name, logo) for the
 * Inertia root view and the global AppConfig JS object.
 *
 * Rules:
 *   - The central host ALWAYS renders the `default` scheme. Tenants cannot
 *     override the central host's appearance.
 *   - A tenant's scheme falls back to `default` when the saved value is
 *     null, empty, or not present in config('color-schemes').
 *   - `appName()` falls back to config('app.name') for both central and
 *     tenants without a custom app_name.
 *   - `logoUrl()` returns null when no tenant logo is set; consumers
 *     should render text/initials in that case.
 */
class ThemeResolver
{
    public const DEFAULT_SCHEME = 'default';

    public static function resolve(): string
    {
        $tenant = self::currentTenant();
        if ($tenant === null) {
            return self::DEFAULT_SCHEME;
        }

        $scheme = $tenant->color_scheme;

        if (! is_string($scheme) || $scheme === '') {
            return self::DEFAULT_SCHEME;
        }

        return array_key_exists($scheme, config('color-schemes', []))
            ? $scheme
            : self::DEFAULT_SCHEME;
    }

    public static function appName(): string
    {
        $tenant = self::currentTenant();

        return $tenant?->app_name ?: (string) config('app.name');
    }

    public static function logoUrl(): ?string
    {
        $tenant = self::currentTenant();
        if ($tenant === null) {
            return null;
        }

        $path = $tenant->logo_path;

        return $path ? Storage::url($path) : null;
    }

    /**
     * Public payload for Inertia shared props + the AppConfig <script>.
     *
     * @return array{name: string, isCentral: bool, schemes: array<int, string>, appName: string, logoUrl: ?string}
     */
    public static function toArray(): array
    {
        return [
            'name' => self::resolve(),
            'isCentral' => self::currentTenant() === null,
            'schemes' => array_keys(config('color-schemes', [])),
            'appName' => self::appName(),
            'logoUrl' => self::logoUrl(),
        ];
    }

    private static function currentTenant(): ?Tenant
    {
        if (! function_exists('tenancy')) {
            return null;
        }

        $tenant = tenancy()->tenant;

        if (! $tenant instanceof Tenant) {
            return null;
        }

        return $tenant;
    }
}
