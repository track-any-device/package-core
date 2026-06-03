<?php

namespace TrackAnyDevice\Core\Models;

use TrackAnyDevice\Core\Concerns\BelongsToTenant;
use TrackAnyDevice\Core\Concerns\UsesCentralConnection;
use TrackAnyDevice\Core\Enums\BeatStatus;
use TrackAnyDevice\Core\Enums\BeatZoneType;
use TrackAnyDevice\Core\Enums\GeoFenceType;
use TrackAnyDevice\Core\Database\Factories\BeatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['tenant_id', 'user_id', 'parent_id', 'name', 'description', 'geo_fence_type', 'zone_type', 'coordinates', 'beat_template_id', 'supervisor_id', 'status'])]
class Beat extends Model
{
    /** @use HasFactory<BeatFactory> */
    use BelongsToTenant, HasFactory, UsesCentralConnection;

    protected function casts(): array
    {
        return [
            'geo_fence_type' => GeoFenceType::class,
            'zone_type'      => BeatZoneType::class,
            'status'         => BeatStatus::class,
            'coordinates'    => 'array',
        ];
    }

    // ── Hierarchy ──────────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Beat::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Beat::class, 'parent_id');
    }

    /**
     * Recursively collect this beat's ID plus all descendant IDs.
     *
     * @return Collection<int, int>
     */
    public function descendantIds(): Collection
    {
        $allIds = collect([$this->id]);
        $frontier = collect([$this->id]); // IDs whose children we haven't fetched yet

        while ($frontier->isNotEmpty()) {
            $newIds = static::whereIn('parent_id', $frontier->toArray())
                ->pluck('id')
                ->diff($allIds); // only IDs we haven't seen yet

            $allIds = $allIds->merge($newIds);
            $frontier = $newIds; // next iteration expands only newly found IDs
        }

        return $allIds;
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Walk the chain of ancestor beats from this beat upward, eagerly
     * loading each step. Order: nearest parent first, root last.
     *
     * @return Collection<int, Beat>
     */
    public function ancestors(): Collection
    {
        $chain = collect();
        $current = $this->parent;

        while ($current !== null) {
            $chain->push($current);
            $current = $current->parent;
        }

        return $chain;
    }

    // ── Other relationships ────────────────────────────────────────────────────

    /**
     * The field-side leader of this beat. For tenant beats this is an
     * Assignee promoted by a tenant_user from the beats page. For personal
     * beats (user_id set) the owning user is implicitly the supervisor;
     * use `personalSupervisor()` to resolve that.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Assignee::class, 'supervisor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPersonal(): bool
    {
        return $this->user_id !== null && $this->tenant_id === null;
    }

    /**
     * Template this beat was created from (and is synced to). When the
     * admin updates the template, this beat's polygon is replaced.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(BeatTemplate::class, 'beat_template_id');
    }

    /**
     * Resolve the supervisor consistently across both beat types:
     *   - tenant beats → the promoted assignee
     *   - personal beats → the owning user (returns User instance)
     */
    public function personalSupervisor(): User|Assignee|null
    {
        if ($this->isPersonal()) {
            return $this->user;
        }

        return $this->supervisor;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('beat_role');
    }

    public function supervisorUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->wherePivot('beat_role', 'supervisor');
    }

    public function staffUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->wherePivot('beat_role', 'staff');
    }

    public function beatAssignments(): HasMany
    {
        return $this->hasMany(BeatAssignment::class);
    }

    public function activeBeatAssignments(): HasMany
    {
        return $this->hasMany(BeatAssignment::class)->whereNull('effective_to');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    // ── Type helpers ──────────────────────────────────────────────────────────

    public function isPolygon(): bool
    {
        return $this->geo_fence_type === GeoFenceType::Polygon
            || $this->geo_fence_type === GeoFenceType::Hexagon;
    }

    public function isCircle(): bool
    {
        return $this->geo_fence_type === GeoFenceType::Circle;
    }
}
