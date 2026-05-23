---
name: tad-assignment-management
description: Assign, transfer, and return TAD devices using AssignmentService and BeatAssignmentService — covering device-to-assignee lifecycle, beat assignments, critical incident guards, and audit trail. Use when assigning devices to field personnel, transferring custody, recording returns, or managing beat zones for devices.
---

# TAD Assignment Management

## Assignee vs User

- **Assignee** — a named field entity (person, vehicle, post) that carries devices. Lives in the `assignees` table, scoped to a tenant. Has a `type` (from `AssigneeType`) and optional `metadata` for contact details.
- **User** — a platform account. End-users can own devices via the `user_devices` pivot, but fleet assignments go through `Assignee`.

## AssignmentService

Always use `AssignmentService` — never update `DeviceAssignment` directly. It handles guards, status transitions, and wraps everything in a DB transaction.

```php
use TrackAnyDevice\Core\Services\AssignmentService;

$service = app(AssignmentService::class);
```

### Assign a device

```php
$assignment = $service->assign(
    device: $device,
    assignee: $assignee,
    assignedBy: $user,
    conditionOut: 'good',   // optional — default 'good'
    notes: null,            // optional
);
```

Throws `AssignmentException` if the device already has an active assignment. Resolve the conflict first using `transfer()` or `returnDevice()`.

### Transfer to a new assignee

```php
$assignment = $service->transfer(
    device: $device,
    newAssignee: $newAssignee,
    transferredBy: $user,
    conditionOut: 'good',
    conditionIn: 'good',         // condition of the device when collected
    notes: null,
    forceIfCriticalIncidents: false,  // true to bypass the critical-incident guard
);
```

Throws `AssignmentException` if there are unresolved critical incidents and `forceIfCriticalIncidents` is false. Resolve the incidents first, or pass `true` to override.

If no active assignment exists, `transfer()` falls through to `assign()`.

### Return a device

```php
$active = $service->getActiveAssignment($device);

$assignment = $service->returnDevice(
    assignment: $active,
    returnedBy: $user,
    conditionIn: 'good',
    notes: null,
);
```

Throws `AssignmentException` if the assignment is not currently active. Sets device status to `available`.

### Check for active critical incidents

```php
if ($service->hasActiveCriticalIncidents($device)) {
    // Block transfer / flag for review
}
```

## DeviceAssignment model

```php
use TrackAnyDevice\Core\Enums\DeviceAssignmentStatus;

$assignment->status;        // DeviceAssignmentStatus: active, transferred, returned
$assignment->assigned_at;   // CarbonImmutable
$assignment->returned_at;   // CarbonImmutable|null
$assignment->condition_out; // string — device condition at issue
$assignment->condition_in;  // string|null — device condition on return
$assignment->notes;         // string|null

$assignment->isActive();    // bool helper
$assignment->device;        // Device relation
$assignment->assignee;      // Assignee relation
```

Get the active assignment for a device:

```php
// Via service (recommended)
$active = $service->getActiveAssignment($device);

// Via model scope
$active = $device->activeDeviceAssignment()->first();
```

## Creating an assignee

```php
use TrackAnyDevice\Core\Models\Assignee;

$assignee = Assignee::create([
    'tenant_id'       => $tenant->id,
    'assignee_type_id' => $type->id,
    'name'            => 'Officer Ahmed',
    'code'            => 'OFC-001',
    'metadata'        => [
        'phone' => '923001234567',
        'badge' => 'B-442',
    ],
]);
```

## Beat assignments

Beat assignments are separate from device assignments. A device can be assigned to an Assignee (custody) AND to a Beat (geo-fence zone) simultaneously.

```php
use TrackAnyDevice\Core\Services\BeatAssignmentService;

$beatService = app(BeatAssignmentService::class);

// Assign device to beat
$beatAssignment = $beatService->assign($device, $beat, $assignedBy);

// End beat assignment
$beatService->end($beatAssignment, $endedBy);
```

`BeatAssignment` drives `CheckBeatViolation` — a device without an active beat assignment is never checked for geo-fence violations.

## AssignmentException

Both services throw `TrackAnyDevice\Core\Exceptions\AssignmentException` for business-rule violations. Catch it in controllers:

```php
use TrackAnyDevice\Core\Exceptions\AssignmentException;

try {
    $assignment = $service->assign($device, $assignee, $user);
} catch (AssignmentException $e) {
    return back()->withErrors(['device' => $e->getMessage()]);
}
```
