<?php

namespace TrackAnyDevice\Core\Services;

use TrackAnyDevice\Core\Enums\BeatAssignmentStatus;
use TrackAnyDevice\Core\Exceptions\AssignmentException;
use TrackAnyDevice\Core\Models\Beat;
use TrackAnyDevice\Core\Models\BeatAssignment;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Models\User;
use Illuminate\Support\Facades\DB;

class BeatAssignmentService
{
    /**
     * Assign a device to a beat. Fails if the device already has an active beat assignment.
     */
    public function assign(
        Device $device,
        Beat $beat,
        User $assignedBy,
        ?string $reason = null,
        ?string $notes = null,
    ): BeatAssignment {
        $this->guardAgainstActiveAssignment($device);

        return BeatAssignment::create([
            'device_id' => $device->id,
            'beat_id' => $beat->id,
            'assigned_by' => $assignedBy->id,
            'effective_from' => now(),
            'status' => BeatAssignmentStatus::Active,
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }

    /**
     * Transfer a device from its current beat to a new one, preserving audit history.
     */
    public function transfer(
        Device $device,
        Beat $newBeat,
        User $transferredBy,
        ?string $reason = null,
        ?string $notes = null,
    ): BeatAssignment {
        $current = $this->getActiveAssignment($device);

        if ($current === null) {
            return $this->assign($device, $newBeat, $transferredBy, $reason, $notes);
        }

        return DB::transaction(function () use ($device, $current, $newBeat, $transferredBy, $reason, $notes) {
            $current->update([
                'status' => BeatAssignmentStatus::Transferred,
                'effective_to' => now(),
            ]);

            return BeatAssignment::create([
                'device_id' => $device->id,
                'beat_id' => $newBeat->id,
                'assigned_by' => $transferredBy->id,
                'effective_from' => now(),
                'status' => BeatAssignmentStatus::Active,
                'reason' => $reason,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * End the active beat assignment for a device.
     */
    public function end(
        Device $device,
        User $endedBy,
        ?string $reason = null,
    ): BeatAssignment {
        $current = $this->getActiveAssignment($device);

        if ($current === null) {
            throw new AssignmentException("Device [{$device->imei}] has no active beat assignment.");
        }

        $current->update([
            'status' => BeatAssignmentStatus::Ended,
            'effective_to' => now(),
            'reason' => $reason,
        ]);

        return $current->fresh();
    }

    public function getActiveAssignment(Device $device): ?BeatAssignment
    {
        return BeatAssignment::where('device_id', $device->id)
            ->where('status', BeatAssignmentStatus::Active)
            ->latest()
            ->first();
    }

    private function guardAgainstActiveAssignment(Device $device): void
    {
        if ($this->getActiveAssignment($device) !== null) {
            throw new AssignmentException(
                "Device [{$device->imei}] already has an active beat assignment. Transfer or end it first."
            );
        }
    }
}
