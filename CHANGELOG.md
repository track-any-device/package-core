# Changelog

## [0.1.0] - 2026-06-02

### Breaking Changes

- **`SignalCreatedEvent`** constructor signature changed: now requires `deviceId`, `imei`, `tenantId`, `userId`, `signal` (named args). The `Device::find()` call in `broadcastWith()` has been removed.

### Added

- `LocationsBatchEvent` — per-tenant batched position push (event name `locations.batch`).
- `SignalBroadcastBuffer` — Redis-backed last-write-wins buffer keyed by tenant for debouncing location broadcasts.
- `FlushSignalBroadcasts` artisan command (`signals:flush`) — one-shot or `--daemon --interval=2` mode.
- `config/device_logs.php` — `admin_sample_rate` (default 1%) and `admin_enabled` kill switch.
- `LowBattery` and `GeofenceExit` cases added to `SignalEventType` enum.
- Tests under `tests/Feature/Broadcasting/`.

### Changed

- `SignalCreatedEvent` switched from `ShouldBroadcastNow` to `ShouldBroadcast` (queued). Channels changed from `device.{id}` to `tenant.{id}.locations` / `user.{id}.devices`.
- `DeviceUpdatedEvent` dropped `admin.devices` and `device.{id}` channels; switched to `ShouldBroadcast` with private tenant/user channels only.
- `DeviceOnboardedEvent` dropped `admin.devices` and `device.{id}` channels; switched to `ShouldBroadcast` with private tenant/user channels only.
- `DeviceLogEvent` gained `includeAdminChannel` parameter; admin firehose is now sampled.
- `SignalService::record()` now routes critical signals (SOS, LowBattery, GeofenceExit) to immediate broadcast and buffers routine signals.
- `DeviceLog` facade now decides admin-channel inclusion via sampling rate config.
- `CoreServiceProvider` registers the new `signals:flush` command and merges/publishes `device_logs` config.
