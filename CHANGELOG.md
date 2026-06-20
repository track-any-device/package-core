# Changelog

## [Unreleased]

### Breaking Changes

- Catalog cut: removed the obsolete component catalogue — models `Chip`, `ComputeBoard`, `Sensor`, `ConnectingCable`, `ChargingSet`, `GsmNetwork`, their tables, the five `device_type_*` build-spec pivots, and `DeviceType`'s build-spec relations. `ProductType` is now `DeviceType`-only. The sellable catalog is DeviceType (app) + Accessory/CMS (Sanity).

### Added

- Shop checkout flow: `device_orders` extended with `product_id`, `claim_code`, `shipping_name`, `shipping_phone`, `shipping_address` (json), `billing_address` (json), `payment_method`, `total_amount`, `currency` columns.
- `PaymentMethod` enum (`CashOnDelivery`).
- `Product.max_order_quantity` column — per-product direct checkout limit.
- `DeviceOrder::generateClaimCode()` — unique 8-char alphanumeric claim code.
- `DeviceOrder::product()` relationship.
- `DeviceOrder::isDelivered()`, `DeviceOrder::isCancelled()`, `DeviceOrder::referenceNumber()` helpers.

### Changed

- `device_orders.tenant_id` and `device_orders.device_type_id` are now nullable (direct shop purchases may not have a tenant or device type).

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
