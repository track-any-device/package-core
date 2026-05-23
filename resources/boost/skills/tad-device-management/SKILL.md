---
name: tad-device-management
description: Create, configure, and manage TAD devices — covering device creation, driver resolution, status lifecycle, signal recording, and outbound commands. Use when working with the Device model, DeviceServiceProvider, SignalService, or any device-related jobs.
---

# TAD Device Management

## Device model

Key columns and their meaning:

| Column | Type | Notes |
|---|---|---|
| `imei` | string | Hardware identifier — unique |
| `gsm_number` | string | Phone number for SMS communication |
| `status` | `DeviceStatus` | Coarse status: `warehouse`, `inventory`, `available`, `assigned`, `in_service` |
| `onboarding_status` | `OnboardingStatus` | Fine-grained: `pending`, `sim_added`, `configured`, `verified` |
| `tenant_id` | int\|null | Owning tenant — null when in stock |
| `user_id` | int\|null | End-user owner — null for fleet devices |
| `last_lat/lon` | decimal | Snapshot of last known position |
| `last_signal_at` | datetime | When the device last reported |
| `connection_attempt_count` | int | Offline-recovery backoff counter |

## Creating a device

```php
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Enums\DeviceStatus;
use TrackAnyDevice\Core\Enums\OnboardingStatus;

$device = Device::create([
    'device_type_id' => $deviceType->id,
    'imei'           => '123456789012345',
    'serial_number'  => 'SN-001',
    'sim_number'     => '8960123456789',
    'gsm_number'     => '923001234567',
    'status'         => DeviceStatus::Warehouse,
    'onboarding_status' => OnboardingStatus::Pending,
    'is_approved'    => false,
    'is_visible_to_tenant' => false,
]);
```

## Status lifecycle

```
Warehouse → (SIM added) → Available → (assigned to tenant/user) → Assigned → (activated) → InService
```

Use `reconciledStatus()` to derive the correct status from ownership state:

```php
$device->update(['status' => $device->reconciledStatus()]);
```

Never set `status` manually unless you have a specific reason — let `reconciledStatus()` or `AssignmentService` drive it.

## Resolving a driver

Drivers are keyed by `DeviceType::slug`. Always resolve through the service provider:

```php
use TrackAnyDevice\Core\Providers\DeviceServiceProvider;

$driver = DeviceServiceProvider::driverFor($device->deviceType->slug);

// Check the effective driver (per-device override or device type default)
$driverClass = $device->effectiveDriverClass();
```

Registered slugs: `p901`, `gf-07`, `jt808`, `aot120`, `tad101` (via separate package).

## Onboarding a device

Dispatch `OnboardDeviceJob` — it is idempotent and skips devices already in `verified` state:

```php
use TrackAnyDevice\Core\Jobs\OnboardDeviceJob;

OnboardDeviceJob::dispatch($device->id);
```

The job calls `$driver->onboardingAction($device)` and advances `onboarding_status` to `configured`. The device transitions to `verified` when it sends its first registration signal.

## Recording signals

```php
use TrackAnyDevice\Core\Services\SignalService;
use TrackAnyDevice\Drivers\ValueObjects\SignalObject;

$service = app(SignalService::class);

$signal = $service->record($signalObject, $device);
// Updates device snapshot columns (last_lat, last_lon, last_signal_at, battery_level)
// Dispatches SignalCreatedEvent
// Returns a Signal projection
```

`SignalService` is a no-op when `INFLUXDB_ENABLED=false` — safe to call in all environments.

## Querying signal history

```php
$signals = $service->queryHistory(
    deviceId: $device->id,
    from: now()->subHours(24),
    to: now(),
    limit: 500,
    eventType: 'location',   // optional filter
);

$latest = $service->latestForDevice($device->id); // last 30 days, 100 records
```

## Sensor resolution

Each device can have directly-attached sensors, falling back to device type defaults:

```php
$slugs = $device->effectiveSensorSlugs(); // ['gps', 'gsm', 'battery']
```

## Offline recovery

The `devices:detect-offline` command drives `OfflineDeviceRecoveryService`. Devices are skipped when `next_connection_attempt_at` is in the future. The counter is cleared automatically by `SignalService` when the device reports in:

```php
// Manual reset (admin action)
$device->forceFill([
    'connection_attempt_count' => 0,
    'next_connection_attempt_at' => null,
])->save();
```

## See also

- `references/signal-object.md` — `SignalObject` value object fields
- `references/device-commands.md` — queuing outbound device commands
