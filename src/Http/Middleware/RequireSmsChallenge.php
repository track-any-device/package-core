<?php

namespace TrackAnyDevice\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSmsChallenge
{
    /** Routes exempt from the SMS challenge gate. */
    private const EXCLUDED_ROUTES = [
        'sms.challenge',
        'sms.challenge.verify',
        'sms.challenge.resend',
        'logout',
        'verification.notice',
        'verification.send',
        'verification.verify',
        'password.request',
        'password.email',
        'password.reset',
        'password.update',
        'phone.edit',
        'phone.send',
        'phone.verify',
        'phone.verify.submit',
        'phone.resend',
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

        if ($request->session()->get('sms_2fa_verified')) {
            return $next($request);
        }

        return redirect()->route('sms.challenge');
    }
}
