<?php

namespace TrackAnyDevice\Core\Jobs;

use TrackAnyDevice\Core\Enums\AlertRuleEventType;
use TrackAnyDevice\Core\Enums\BeatAssignmentStatus;
use TrackAnyDevice\Core\Enums\IncidentPriority;
use TrackAnyDevice\Core\Enums\IncidentStatus;
use TrackAnyDevice\Core\Models\Beat;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Models\Incident;
use TrackAnyDevice\Core\Services\GeoFence;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Level-aware beat-violation detector.
 *
 * Algorithm:
 *   1. Resolve the device's assigned (leaf) beat from its active beat
 *      assignment.
 *   2. Walk the chain: assigned → parent → grandparent → … until we
 *      find a beat whose polygon contains the point (or run out).
 *   3. Level = how many beats in the chain the device sits outside:
 *        - inside the assigned beat       → level 0, no incident
 *        - outside assigned, inside parent → level 1
 *        - outside parent, inside grand   → level 2
 *        - outside everything             → level = chain length
 *
 * One open incident per device at a time. As the device escalates
 * further out, the existing incident's level is bumped (and beat_id
 * advances to the outermost still-violated beat for context). On
 * re-entry to the assigned beat, the incident auto-resolves.
 */
class CheckBeatViolation implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $deviceId,
        public readonly float $latitude,
        public readonly float $longitude,
    ) {}

    public function handle(GeoFence $geoFence): void
    {
        $device = Device::find($this->deviceId);
        if ($device === null) {
            return;
        }

        $beatAssignment = $device->beatAssignments()
            ->where('status', BeatAssignmentStatus::Active)
            ->with('beat')
            ->latest()
            ->first();

        if ($beatAssignment === null) {
            return;
        }

        $assignedBeat = $beatAssignment->beat;
        $chain = collect([$assignedBeat])->merge($assignedBeat->ancestors());

        // Find the first beat in the chain that contains the point; the
        // device is "inside" everything from there upward. Level = the
        // count of beats it is OUTSIDE relative to the chain (0 = inside
        // assigned beat; chain count = outside every beat).
        $level = 0;
        $outermostViolated = null;

        foreach ($chain as $beat) {
            if ($geoFence->isInsideBeat($beat, $this->latitude, $this->longitude)) {
                break;
            }
            $level++;
            $outermostViolated = $beat;
        }

        $existingViolation = Incident::query()
            ->where('device_id', $this->deviceId)
            ->where('event_type', AlertRuleEventType::BeatViolation->value)
            ->whereIn('status', [
                IncidentStatus::Open->value,
                IncidentStatus::Acknowledged->value,
                IncidentStatus::Escalated->value,
            ])
            ->latest()
            ->first();

        if ($level === 0) {
            // Device is inside its assigned beat — auto-resolve any
            // open violation.
            if ($existingViolation !== null) {
                $existingViolation->update([
                    'status' => IncidentStatus::Resolved,
                    'resolved_at' => now(),
                    'resolution_notes' => 'Auto-resolved: device re-entered assigned beat.',
                ]);
            }

            return;
        }

        // Device is outside at least one beat in the chain.
        if ($existingViolation === null) {
            $this->createOrReopenViolation(
                device: $device,
                assignedBeat: $assignedBeat,
                outermostViolated: $outermostViolated,
                level: $level,
            );

            return;
        }

        // An incident already exists — update its level + beat context
        // when the device has moved outward (or inward but still violating).
        $changes = [];

        if ($existingViolation->level !== $level) {
            $changes['level'] = $level;
        }

        if ($outermostViolated && $existingViolation->beat_id !== $outermostViolated->id) {
            $changes['beat_id'] = $outermostViolated->id;
        }

        // Update lat/lng + bump the in-flight status to Escalated if the
        // device crossed further out than before.
        $changes['latitude'] = $this->latitude;
        $changes['longitude'] = $this->longitude;

        if ($level > ($existingViolation->level ?? 0)
            && $existingViolation->status === IncidentStatus::Open) {
            $changes['status'] = IncidentStatus::Escalated;
        }

        $existingViolation->update($changes);
    }

    private function createOrReopenViolation(
        Device $device,
        Beat $assignedBeat,
        ?Beat $outermostViolated,
        int $level,
    ): void {
        $activeAssignment = $device->deviceAssignments()
            ->where('status', 'active')
            ->latest()
            ->first();

        $recentlyResolved = Incident::query()
            ->where('device_id', $device->id)
            ->where('beat_id', $assignedBeat->id)
            ->where('event_type', AlertRuleEventType::BeatViolation->value)
            ->where('status', IncidentStatus::Resolved->value)
            ->where('resolved_at', '>=', now()->subDays(7))
            ->latest('resolved_at')
            ->first();

        if ($recentlyResolved !== null) {
            $history = $recentlyResolved->reopen_history ?? [];
            $history[] = now()->toISOString();

            $recentlyResolved->update([
                'status' => $level >= 2 ? IncidentStatus::Escalated : IncidentStatus::Open,
                'level' => $level,
                'beat_id' => $outermostViolated?->id ?? $assignedBeat->id,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'triggered_at' => now(),
                'resolved_at' => null,
                'resolution_notes' => null,
                'reopen_count' => $recentlyResolved->reopen_count + 1,
                'reopen_history' => $history,
            ]);

            return;
        }

        Incident::create([
            'tenant_id' => $device->tenant_id,
            'device_id' => $device->id,
            'assignee_id' => $activeAssignment?->assignee_id,
            'beat_id' => $outermostViolated?->id ?? $assignedBeat->id,
            'event_type' => AlertRuleEventType::BeatViolation,
            'priority' => $level >= 2 ? IncidentPriority::Critical : IncidentPriority::High,
            'level' => $level,
            'status' => $level >= 2 ? IncidentStatus::Escalated : IncidentStatus::Open,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'triggered_at' => now(),
            'reopen_count' => 0,
        ]);
    }
}
