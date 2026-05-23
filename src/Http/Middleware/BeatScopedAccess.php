<?php

namespace TrackAnyDevice\Core\Http\Middleware;

use TrackAnyDevice\Core\Enums\Role;
use TrackAnyDevice\Core\Services\BeatScope;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class BeatScopedAccess
{
    public function __construct(private readonly BeatScope $scope) {}

    /**
     * Share the user's allowed beat IDs with all views/Inertia responses
     * so controllers and front-end can scope their queries.
     *
     * Admin users receive null (no restriction). Supervisors/Staff receive
     * a collection of beat IDs they are assigned to.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->role !== Role::Admin) {
            $beatIds = $this->scope->allowedBeatIds($user);

            // Share with Inertia / Blade views
            View::share('allowedBeatIds', $beatIds);

            // Attach to the request so controllers can read it without re-querying
            $request->attributes->set('allowed_beat_ids', $beatIds);
        } else {
            View::share('allowedBeatIds', null);
            $request->attributes->set('allowed_beat_ids', null);
        }

        return $next($request);
    }
}
