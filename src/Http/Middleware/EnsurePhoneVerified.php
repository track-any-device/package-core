<?php

namespace TrackAnyDevice\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneVerified
{
    /** Routes that are always accessible without phone verification. */
    private const EXCLUDED_ROUTES = [
        'phone.edit',
        'phone.send',
        'phone.verify',
        'phone.verify.submit',
        'logout',
        'verification.notice',
        'verification.send',
        'verification.verify',
        'password.request',
        'password.email',
        'password.reset',
        'password.update',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::EXCLUDED_ROUTES, true)) {
            return $next($request);
        }

        if (! $user->hasPhone()) {
            return redirect()->route('phone.edit');
        }

        if (! $user->hasVerifiedPhone()) {
            return redirect()->route('phone.verify');
        }

        return $next($request);
    }
}
