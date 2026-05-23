# Device Commands

Outbound commands are queued via `QueueDeviceCommand` and tracked in the `device_commands` table.

## Queuing a command

```php
use TrackAnyDevice\Core\Jobs\QueueDeviceCommand;

QueueDeviceCommand::dispatch(
    deviceId: $device->id,
    command: 'location',     // command name understood by the driver
    payload: [],             // optional key/value parameters
    initiatedBy: $user->id, // optional — for audit trail
);
```

## Command status lifecycle

```
Pending → Sent → (device responds) → Acknowledged
                                   → Failed (timeout / error)
```

Status is tracked via `DeviceCommand` model and `DeviceCommandStatus` enum. `DeviceCommandObserver` fires events on status transitions.

## Querying pending commands

```php
use TrackAnyDevice\Core\Models\DeviceCommand;
use TrackAnyDevice\Core\Enums\DeviceCommandStatus;

$pending = DeviceCommand::where('device_id', $device->id)
    ->where('status', DeviceCommandStatus::Pending)
    ->get();
```

## Service helper

```php
use TrackAnyDevice\Core\Services\DeviceCommandService;

$service = app(DeviceCommandService::class);
$command = $service->queue($device, 'location');
```
