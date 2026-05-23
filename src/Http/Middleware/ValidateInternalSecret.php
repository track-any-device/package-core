<?php

namespace TrackAnyDevice\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateInternalSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('app.internal_secret');

        if (empty($secret) || ! hash_equals($secret, (string) $request->header('X-Internal-Secret'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
