<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records the authenticated user's IP and (optionally) browser-reported
 * geolocation on every request.
 *
 * The frontend sends coords via the X-Browser-Latitude / X-Browser-Longitude
 * headers when the user has granted geolocation permission. IP is always
 * captured. Writes happen at most once per minute per user to avoid
 * write-amplification on busy sessions.
 */
class CaptureAuthLocation
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $updates = [];

        $ip = $request->ip();
        if ($ip && $user->last_ip_address !== $ip) {
            $updates['last_ip_address'] = $ip;
        }

        $lat = $request->header('X-Browser-Latitude');
        $lng = $request->header('X-Browser-Longitude');

        if (is_numeric($lat) && is_numeric($lng)) {
            $updates['browser_latitude'] = (float) $lat;
            $updates['browser_longitude'] = (float) $lng;
            $updates['location_consented_at'] = now();
        }

        if ($updates !== []) {
            $user->forceFill($updates)->saveQuietly();
        }

        return $next($request);
    }
}
