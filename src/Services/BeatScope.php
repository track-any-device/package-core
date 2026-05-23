<?php

namespace TrackAnyDevice\Core\Services;

use TrackAnyDevice\Core\Enums\Role;
use TrackAnyDevice\Core\Models\Beat;
use TrackAnyDevice\Core\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BeatScope
{
    /**
     * Return the beat IDs the user is allowed to see, or null if unrestricted (Admin).
     * Expands to include all descendants so a provincial supervisor sees city and sub-beats.
     *
     * @return Collection<int, int>|null null = no restriction (admin)
     */
    public function allowedBeatIds(User $user): ?Collection
    {
        if ($user->role === Role::Admin) {
            return null;
        }

        $directIds = $user->beats()->pluck('beats.id');

        return $directIds->flatMap(function (int $id) {
            $beat = Beat::find($id);

            return $beat ? $beat->descendantIds() : collect([$id]);
        })->unique()->values();
    }

    /**
     * Whether the user is restricted to specific beats.
     */
    public function isScoped(User $user): bool
    {
        return $user->role !== Role::Admin;
    }

    /**
     * Apply beat scoping to a device query (via beat_assignments).
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeDeviceQuery($query, User $user)
    {
        $beatIds = $this->allowedBeatIds($user);

        if ($beatIds === null) {
            return $query;
        }

        return $query->whereHas('beatAssignments', function ($q) use ($beatIds) {
            $q->whereIn('beat_id', $beatIds)->where('status', 'active');
        });
    }

    /**
     * Apply beat scoping to an incidents query.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeIncidentQuery($query, User $user)
    {
        $beatIds = $this->allowedBeatIds($user);

        if ($beatIds === null) {
            return $query;
        }

        return $query->whereIn('beat_id', $beatIds);
    }
}
