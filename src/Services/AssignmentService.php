<?php

namespace TrackAnyDevice\Core\Services;

use TrackAnyDevice\Core\Enums\DeviceAssignmentStatus;
use TrackAnyDevice\Core\Enums\DeviceStatus;
use TrackAnyDevice\Core\Enums\IncidentPriority;
use TrackAnyDevice\Core\Enums\IncidentStatus;
use TrackAnyDevice\Core\Exceptions\AssignmentException;
use TrackAnyDevice\Core\Models\Assignee;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Models\DeviceAssignment;
use TrackAnyDevice\Core\Models\User;
use Illuminate\Support\Facades\DB;

class AssignmentService
{
    /**
     * Assign a device to an assignee. Fails if the device already has an active assignment.
     */
    public function assign(
        Device $device,
        Assignee $assignee,
        User $assignedBy,
        string $conditionOut = 'good',
        ?string $notes = null,
    ): DeviceAssignment {
        $this->guardAgainstActiveAssignment($device);

        return DB::transaction(function () use ($device, $assignee, $assignedBy, $conditionOut, $notes) {
            $assignment = DeviceAssignment::create([
                'device_id' => $device->id,
                'assignee_id' => $assignee->id,
                'assigned_by' => $assignedBy->id,
                'assigned_at' => now(),
                'condition_out' => $conditionOut,
                'status' => DeviceAssignmentStatus::Active,
                'notes' => $notes,
            ]);

            $device->update(['status' => DeviceStatus::Assigned]);

            return $assignment;
        });
    }

    /**
     * Transfer a device from its current assignee to a new one.
     * Warns if the device has unresolved critical incidents.
     */
    public function transfer(
        Device $device,
        Assignee $newAssignee,
        User $transferredBy,
        string $conditionOut = 'good',
        ?string $conditionIn = null,
        ?string $notes = null,
        bool $forceIfCriticalIncidents = false,
    ): DeviceAssignment {
        if (! $forceIfCriticalIncidents && $this->hasActiveCriticalIncidents($device)) {
            throw new AssignmentException(
                "Device [{$device->imei}] has unresolved critical incidents. Resolve them before transferring, or use force."
            );
        }

        $current = $this->getActiveAssignment($device);

        if ($current === null) {
            return $this->assign($device, $newAssignee, $transferredBy, $conditionOut, $notes);
        }

        return DB::transaction(function () use ($device, $current, $newAssignee, $transferredBy, $conditionOut, $conditionIn, $notes) {
            $current->update([
                'status' => DeviceAssignmentStatus::Transferred,
                'returned_at' => now(),
                'condition_in' => $conditionIn,
            ]);

            return DeviceAssignment::create([
                'device_id' => $device->id,
                'assignee_id' => $newAssignee->id,
                'assigned_by' => $transferredBy->id,
                'assigned_at' => now(),
                'condition_out' => $conditionOut,
                'status' => DeviceAssignmentStatus::Active,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Return a device from its current assignee.
     */
    public function returnDevice(
        DeviceAssignment $assignment,
        User $returnedBy,
        string $conditionIn = 'good',
        ?string $notes = null,
    ): DeviceAssignment {
        if (! $assignment->isActive()) {
            throw new AssignmentException('Cannot return a device that is not actively assigned.');
        }

        return DB::transaction(function () use ($assignment, $conditionIn, $notes) {
            $assignment->update([
                'status' => DeviceAssignmentStatus::Returned,
                'returned_at' => now(),
                'condition_in' => $conditionIn,
                'notes' => $notes ?? $assignment->notes,
            ]);

            $assignment->device->update(['status' => DeviceStatus::Available]);

            return $assignment->fresh();
        });
    }

    public function getActiveAssignment(Device $device): ?DeviceAssignment
    {
        return DeviceAssignment::where('device_id', $device->id)
            ->where('status', DeviceAssignmentStatus::Active)
            ->latest()
            ->first();
    }

    public function hasActiveCriticalIncidents(Device $device): bool
    {
        return $device->incidents()
            ->where('priority', IncidentPriority::Critical)
            ->whereIn('status', [IncidentStatus::Open->value, IncidentStatus::Acknowledged->value])
            ->exists();
    }

    private function guardAgainstActiveAssignment(Device $device): void
    {
        if ($this->getActiveAssignment($device) !== null) {
            throw new AssignmentException(
                "Device [{$device->imei}] already has an active assignment. Transfer or return it first."
            );
        }
    }
}
