# track-any-device/core

Core package for the Track Any Device (TAD) platform. Provides models, migrations, seeders, middleware, jobs, services, and workflow infrastructure shared across all TAD applications.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ^8.3 |
| Laravel | ^13.7 |
| laravel/fortify | ^1.0 |
| laravel/sanctum | ^4.0 |
| stancl/tenancy | ^3.10 |
| track-any-device/sms-gateway | `*@dev` |

---

## Installation

Add the package to your host application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/track-any-device/package-core"
        }
    ],
    "require": {
        "track-any-device/core": "dev-main"
    }
}
```

Then install:

```bash
composer install
```

`CoreServiceProvider` is auto-discovered via the `extra.laravel.providers` entry in `composer.json`. The remaining service providers must be registered manually in `bootstrap/providers.php` (see [Service Providers](#service-providers)).

---

## Configuration

### Environment variables

| Key | Default | Description |
|---|---|---|
| `APP_SURFACE` | `core` | Controls which surfaces load migrations. Set to `core` (or leave unset) to load all package migrations. Set to any other value to suppress them (e.g. for worker-only containers). |
| `APP_LOGIN_DOMAIN` | — | Hostname of the dedicated identity server (e.g. `login.example.com`). Used by `AuthorizeTenantAccess` to build the OAuth authorize URL. Falls back to `APP_DOMAIN` when unset. |
| `APP_DOMAIN` | `localhost` | Central / root domain of the application. |
| `APP_INTERNAL_SECRET` | — | Shared secret for internal service-to-service calls. Validated by `ValidateInternalSecret` middleware via the `X-Internal-Secret` header. |

### InfluxDB (telemetry)

Signals (device telemetry) are stored in InfluxDB when enabled. Add a `config/influxdb.php` file to your host application:

```php
return [
    'enabled' => env('INFLUXDB_ENABLED', false),
    'url'     => env('INFLUXDB_URL', 'http://localhost:8086'),
    'token'   => env('INFLUXDB_TOKEN', ''),
    'bucket'  => env('INFLUXDB_BUCKET', 'signals'),
    'org'     => env('INFLUXDB_ORG', 'tad'),
];
```

| Key | Default | Description |
|---|---|---|
| `INFLUXDB_ENABLED` | `false` | Set to `true` to enable time-series signal writes and queries. When `false`, `SignalService` no-ops silently. |
| `INFLUXDB_URL` | `http://localhost:8086` | InfluxDB instance URL |
| `INFLUXDB_TOKEN` | — | Authentication token |
| `INFLUXDB_BUCKET` | `signals` | Bucket name for signal data |
| `INFLUXDB_ORG` | `tad` | InfluxDB organisation |

### Fleet thresholds

Add a `config/fleet.php` file to configure alarm thresholds:

```php
return [
    'overspeed_threshold'   => env('OVERSPEED_THRESHOLD_KMH', 80),
    'low_battery_threshold' => env('LOW_BATTERY_THRESHOLD', 20),
];
```

### Workflows

Add a `config/workflows.php` file to customise webhook retry backoff (useful in tests):

```php
return [
    // Seconds to sleep between webhook retry attempts [attempt-1, attempt-2, ...]
    'webhook_backoff' => [2, 8],
];
```

---

## Service Providers

`CoreServiceProvider` is auto-discovered. Register the rest manually in `bootstrap/providers.php`:

```php
return [
    // Auto-discovered — listed here for clarity
    TrackAnyDevice\Core\CoreServiceProvider::class,

    // Must be registered manually
    TrackAnyDevice\Core\Providers\TenancyServiceProvider::class,
    TrackAnyDevice\Core\Providers\DeviceServiceProvider::class,
    TrackAnyDevice\Core\Providers\FortifyServiceProvider::class,
];
```

### CoreServiceProvider

- Loads package migrations when `config('app.surface')` is `core` or unset.
- Registers Artisan commands.
- Registers the package factory name resolver.

### TenancyServiceProvider

Configures `stancl/tenancy` events, boots the tenancy middleware priority stack, and maps `routes/tenant.php` from the host application (if it exists) under the `web` middleware group.

### DeviceServiceProvider

Registers device driver bindings keyed by `DeviceType` slug:

| Slug | Driver |
|---|---|
| `p901` | `P901Driver` |
| `gf-07` | `GF07Driver` |
| `jt808` | `Jt808Driver` |
| `aot120` | `AOT120Driver` |
| `tad101` | Registered by `track-any-device/tad101` package |

Resolve a driver in application code:

```php
use TrackAnyDevice\Core\Providers\DeviceServiceProvider;

$driver = DeviceServiceProvider::driverFor($device->deviceType->slug);
```

### FortifyServiceProvider

Configures Fortify views (Inertia), actions, and rate limiting. Requires the host application to provide:

- `App\Actions\Fortify\CreateNewUser`
- `App\Actions\Fortify\ResetUserPassword`

---

## Migrations & Seeders

### Running migrations

```bash
php artisan migrate
```

Migrations are loaded automatically by `CoreServiceProvider` when `APP_SURFACE` is `core` (or unset).

### Available seeders

Run the full seed sequence:

```bash
php artisan db:seed --class="TrackAnyDevice\Core\Database\Seeders\DatabaseSeeder"
```

Individual seeders:

| Seeder | Description |
|---|---|
| `AdminSeeder` | Creates the default admin user |
| `TenantSeeder` | Seeds example tenant(s) |
| `DeviceTypeSeeder` | Seeds supported device type records |
| `DriverSeeder` | Seeds driver records |
| `SensorSeeder` | Seeds sensor definitions |
| `GsmNetworkSeeder` | Seeds GSM network records |
| `CountrySeeder` | Seeds country / dialling-code data |
| `AlertRuleSeeder` | Seeds default global alert rules |
| `AssigneeTypeSeeder` | Seeds default assignee type options |
| `IncidentTaxonomySeeder` | Seeds incident priority and status options |
| `NavLinkSeeder` | Seeds navigation link defaults |
| `PolicyVersionSeeder` | Seeds the current policy version |
| `IndustrySeeder` | Seeds industry classification records |
| `HomePageSeeder` | Seeds default home page CMS content |
| `PublicPageSeeder` | Seeds public-facing CMS pages |
| `BlogSeeder` | Seeds sample blog content |
| `P901CatalogueSeeder` | Seeds P901 product catalogue data |
| `WorkflowSeeder` | Seeds example workflows |
| `SuthraPunjabTenantSeeder` | Seeds the Suthra Punjab demo tenant |

---

## Architecture

### Tenancy model

TAD uses `stancl/tenancy` in a **central-database** configuration — all data lives in one MySQL database. There are no per-tenant databases.

Tenant isolation is enforced at the query layer:

- Models that belong to one tenant (e.g. `Beat`, `Assignee`, `Incident`) use the `BelongsToTenant` trait, which attaches `TenantScope` as a global scope.
- Inside a tenant request (domain resolved by tenancy middleware), `TenantScope` automatically adds `WHERE tenant_id = ?` to every query on those models.
- Outside a tenant context (Filament admin, CLI), the scope is a no-op so admin queries see all rows.

```php
use TrackAnyDevice\Core\Concerns\BelongsToTenant;
use TrackAnyDevice\Core\Concerns\UsesCentralConnection;

class MyModel extends Model
{
    use BelongsToTenant, UsesCentralConnection;
}
```

`UsesCentralConnection` pins the model to the connection defined in `tenancy.database.central_connection` (defaults to `mysql`). It exists to make a future transition to per-tenant databases non-breaking.

### Roles

| Role | `isCentralStaff()` | Description |
|---|---|---|
| `admin` | Yes | Full platform access |
| `supervisor` | Yes | Central staff with supervisory rights |
| `staff` | Yes | Central staff |
| `tenant_user` | No | Member of one or more tenants via `tenant_users` pivot |
| `user` | No | End-user / device owner |

---

## Models

| Model | Connection | Notes |
|---|---|---|
| `User` | Central | Authenticatable; supports Fortify 2FA + Sanctum tokens |
| `Tenant` | Central | Extends stancl's BaseTenant; uses bigint PK |
| `Device` | Central | Soft-deletes; tracks status, onboarding, last signal |
| `DeviceType` | Central | Defines supported hardware variants |
| `Driver` | Central | Maps a device type to a driver class |
| `Sensor` | Central | Sensor definitions attached to device types or devices |
| `Beat` | Central | Geo-fence zone with optional parent hierarchy |
| `BeatAssignment` | Central | Links a device to a beat with time boundaries |
| `Assignee` | Central | Person or asset that can be assigned a device |
| `DeviceAssignment` | Central | Assignment record with condition tracking |
| `Incident` | Central | Alert / alarm event tied to a device and optional beat |
| `AlertRule` | Central | Global or tenant-level rules; `tenant_id = null` means global |
| `Workflow` | Central | Automation graph triggered by events or time |
| `WorkflowRun` | Central | Execution record for a single workflow run |
| `Signal` | InfluxDB | Time-series telemetry; not an Eloquent model |

---

## Services

### `SignalService`

Persists device telemetry to InfluxDB and updates the device's snapshot columns in MySQL.

```php
use TrackAnyDevice\Core\Services\SignalService;

$service = app(SignalService::class);

// Record a signal
$signal = $service->record($signalObject, $device);

// Query history
$signals = $service->queryHistory(
    deviceId: $device->id,
    from: now()->subHours(6),
    to: now(),
    limit: 500,
    eventType: 'location',   // optional
);

// Latest signals (last 30 days)
$signals = $service->latestForDevice($device->id, limit: 100);
```

When `INFLUXDB_ENABLED=false`, all read methods return empty collections and write operations are silently skipped. `SignalCreatedEvent` is still dispatched so observers remain active.

### `AssignmentService`

Manages the lifecycle of device-to-assignee assignments.

```php
use TrackAnyDevice\Core\Services\AssignmentService;

$service = app(AssignmentService::class);

// Assign
$assignment = $service->assign($device, $assignee, $assignedBy);

// Transfer to another assignee
$assignment = $service->transfer(
    device: $device,
    newAssignee: $newAssignee,
    transferredBy: $user,
    forceIfCriticalIncidents: false, // throws if unresolved critical incidents
);

// Return
$assignment = $service->returnDevice($assignment, $returnedBy, conditionIn: 'good');
```

### `BeatAssignmentService`

Manages assignment of devices to beats (geo-fence zones).

### `GeoFence`

Point-in-polygon and point-in-circle checks for beat violation detection.

```php
use TrackAnyDevice\Core\Services\GeoFence;

$geo = app(GeoFence::class);

$inside = $geo->isInsideBeat($beat, $latitude, $longitude);

// Convert a legacy circle beat to polygon vertices
$polygon = $geo->circleToPolygon($lat, $lng, $radiusMetres, points: 64);

// Validate a child beat fits within its parent
$fits = $geo->childFitsWithinParent($parentBeat, $childCoordinates);
```

### `IncidentService`

Creates and manages incident records.

### `OfflineDeviceRecoveryService`

Detects devices that have gone silent and dispatches SMS recovery actions. Used by the `devices:detect-offline` command. Uses exponential backoff (5 → 10 → 20 → 40 → 80 → 160 → 320 → 360 min cap) with a hard limit of 8 attempts.

### `DeviceCommandService`

Queues outbound commands to devices.

---

## Middleware

Register these in your host application's `bootstrap/app.php` or `Http/Kernel.php` as needed.

| Middleware | Description |
|---|---|
| `AuthorizeTenantAccess` | Gate tenant-domain requests; bounces unauthenticated visitors through OAuth SSO. Exempt paths: `/register`, `/sso/callback`. |
| `CheckTenantApproved` | Rejects requests to tenants that are not in `approved` status. |
| `EnsureTenantDomain` | Ensures the request is coming from a tenant subdomain (not central). |
| `EnsureCentralDomain` | Ensures the request is coming from the central domain. |
| `EnsureLoginDomain` | Ensures the request is coming from the login/identity domain. |
| `EnsureMyDomain` | Ensures the request host matches `APP_DOMAIN`. |
| `EnsureTenantApiScope` | Validates that a Sanctum token has a required ability: `->middleware('tenant.scope:devices.read')`. |
| `EnsurePhoneVerified` | Rejects access when the user's phone number is not verified. |
| `RequireSmsChallenge` | Requires a one-time SMS OTP challenge after login (session-based). |
| `InitializeTenancyForRequest` | Thin wrapper around stancl's tenancy initialiser. |
| `GateTenantRegistration` | Blocks tenant self-registration when `registration_enabled = false`. |
| `BeatScopedAccess` | Restricts resource access to the beats a user is assigned to. |
| `CaptureAuthLocation` | Records the user's browser geolocation on successful authentication. |
| `ValidateInternalSecret` | Validates the `X-Internal-Secret` header for service-to-service routes. |
| `HandleAppearance` | Applies the tenant's color scheme / theme to the response. |
| `HandleInertiaRequests` | Shares Inertia props (auth user, tenant, flash) on every request. |

### `ValidateInternalSecret` usage

Protect internal-only routes:

```php
// routes/api.php
Route::middleware('App\Http\Middleware\ValidateInternalSecret')
    ->prefix('internal')
    ->group(function () {
        Route::post('/signal', SignalIngestController::class);
    });
```

The calling service must send:

```
X-Internal-Secret: <value of APP_INTERNAL_SECRET>
```

---

## Artisan Commands

| Command | Schedule | Description |
|---|---|---|
| `devices:detect-offline` | Every 5 min | Detect silent in-service devices and dispatch SMS recovery |
| `workflows:run-scheduled` | Every minute | Dispatch time-triggered workflows that are due |
| `sms:poll-inbox` | Every minute | Poll the SMS gateway inbox and store unprocessed messages |
| `otp:prune` | Daily | Delete expired OTP codes from the database |
| `beats:normalize-to-polygon` | One-time migration | Convert legacy circle beats to polygon vertex arrays |

### Recommended schedule

Add to `routes/console.php` in the host application:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('devices:detect-offline')->everyFiveMinutes();
Schedule::command('workflows:run-scheduled')->everyMinute();
Schedule::command('sms:poll-inbox')->everyMinute();
Schedule::command('otp:prune')->daily();
```

### One-time migration

After deployment, run the beat normalisation command to migrate any legacy circle-format beats to polygons:

```bash
# Preview (no writes)
php artisan beats:normalize-to-polygon --dry-run

# Apply
php artisan beats:normalize-to-polygon
```

---

## Workflows

Workflows are automation graphs stored as JSON in the `workflows` table. Each workflow has a trigger type, a graph of nodes and edges, and optional tenant scoping.

### Trigger types

| Type | Description |
|---|---|
| `incident_opened` | Fires when a new incident is created |
| `incident_escalated` | Fires when an incident is escalated |
| `time` | Fires on a cron schedule (driven by `workflows:run-scheduled`) |

### Action types

| Type | Handler | Description |
|---|---|---|
| `notify` | `NotifyUsersAction` | Send notifications to users or assignees |
| `send_command` | `SendDeviceCommandAction` | Queue a command to the incident's device |
| `escalate_incident` | `EscalateIncidentAction` | Change the incident status to `escalated` |
| `webhook` | `CallWebhookAction` | POST the run context to a tenant-supplied URL (3 retries, 10s timeout) |
| `wait` | `WaitDelayAction` | Pause execution for a specified number of seconds (max 180s) |

### Dispatching a workflow from code

```php
use TrackAnyDevice\Core\Workflows\WorkflowDispatcher;
use TrackAnyDevice\Core\Enums\WorkflowTriggerType;

$dispatcher = app(WorkflowDispatcher::class);
$count = $dispatcher->dispatchForIncident($incident, WorkflowTriggerType::IncidentOpened);
```

### Time-triggered workflow config

Store in the `trigger_config` JSON column:

```json
{
    "cron": "*/15 * * * *",
    "timezone": "Asia/Karachi"
}
```

`timezone` is optional and defaults to `config('app.timezone')`.

---

## Jobs

| Job | Queue | Description |
|---|---|---|
| `OnboardDeviceJob` | default | Runs the driver's onboarding action for a device (idempotent) |
| `ProcessAlarmEvents` | default | Creates/resolves incidents from active alarm flags |
| `CheckBeatViolation` | default | Level-aware beat geo-fence violation checker |
| `QueueDeviceCommand` | default | Sends a queued command through the device's driver |
| `RunWorkflowJob` | default | Executes a workflow graph for a given trigger context |

---

## Events

| Event | Payload | Description |
|---|---|---|
| `SignalCreatedEvent` | `$deviceId`, `SignalObject` | Fired after every signal is recorded |
| `DeviceOnboardedEvent` | `Device` | Fired after `OnboardDeviceJob` completes onboarding |
| `DeviceUpdatedEvent` | `Device` | Fired when device snapshot columns change |
| `DeviceLogEvent` | `Device`, log data | Fired when a device log entry is created |

---

## Enums

All database-persisted enums are backed PHP enums (string-backed). Key enums:

| Enum | Values |
|---|---|
| `Role` | `admin`, `supervisor`, `staff`, `tenant_user`, `user` |
| `DeviceStatus` | `warehouse`, `inventory`, `available`, `assigned`, `in_service` |
| `OnboardingStatus` | `pending`, `sim_added`, `configured`, `verified` |
| `IncidentStatus` | `open`, `acknowledged`, `escalated`, `resolved`, `closed` |
| `IncidentPriority` | `low`, `medium`, `high`, `critical` |
| `AlertRuleEventType` | `sos`, `overspeed`, `low_battery`, `power_failure`, `vibration`, `beat_violation` |
| `WorkflowTriggerType` | `incident_opened`, `incident_escalated`, `time` |
| `WorkflowRunStatus` | `running`, `completed`, `failed` |

---

## Factories

Factories are included for testing. The package registers a factory name guesser in `CoreServiceProvider` so `Model::factory()` resolves to `TrackAnyDevice\Core\Database\Factories\<Model>Factory`.

Available factories: `User`, `Tenant`, `Domain`, `Device`, `DeviceType`, `DeviceCommand`, `DeviceOrder`, `DeviceAssignment`, `Assignee`, `AssigneeType`, `Beat`, `BeatAssignment`, `BeatTemplate`, `AlertRule`, `Incident`, `SsoToken`, `Workflow`.

---

## License

MIT
