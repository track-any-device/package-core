# package-core — AI Instructions

This is the **foundation package** for the Track Any Device platform.
Packagist: `track-any-device/core` | Namespace: `TrackAnyDevice\Core\`

Every other PHP package and server app depends on this package. Changes here affect the entire
platform. Read this file before making any change.

---

## Platform-Wide Rules

These three rules apply in every repository under the `track-any-device` organisation.

**Cross-repo changes: file a GitHub issue first.**
If a task in this repository requires a change in another package or server app — stop. Open a
GitHub issue in the target repository describing exactly what is needed and why. Reference that
issue number in your commit message (`ref track-any-device/{repo}#{n}`). Do not directly edit
files in another repository. When picking up a cross-repo issue, run Claude locally inside that
repository's working directory and work only within its scope.

**Release order: packages before server apps.**
This package is the root of the dependency tree. All other packages and server apps depend on it.
Tag a release here before asking any downstream package or server app to bump their constraint.
Downstream order after `package-core`:
`package-drivers, package-jt808, package-tad101, package-sso-server, package-sso-client,
package-mcp, package-admin (parallel) → server apps → web → mobile-app`.

**Database layer lives here — and only here.**
All Eloquent models, migrations, and seeders for the platform belong in this package.
No migration files in server app repositories. No new Eloquent model classes in server apps.
If a server app needs a new model, add it here, cut a release, then bump the constraint there.

---

## Rule 1 — Plan before implementing

Before writing any code, ask clarifying questions. Present a plan and get explicit agreement.
Only begin once the approach is confirmed. Never start on an ambiguous requirement.

---

## What lives in this package

| Directory | Contents |
|---|---|
| `src/Models/` | All Eloquent models — User, Tenant, Device, Beat, Incident, Signal, Workflow… |
| `src/Enums/` | Platform enums — Role, DeviceStatus, IncidentStatus, AlertRuleEventType… |
| `src/Events/` | Domain events — SignalCreatedEvent, DeviceUpdatedEvent, DeviceLogEvent… |
| `src/Jobs/` | Background jobs — OnboardDeviceJob, ProcessAlarmEvents, CheckBeatViolation… |
| `src/Services/` | Domain services — SignalService, AssignmentService, GeoFence, IncidentService… |
| `src/Concerns/` | Model traits — BelongsToTenant, HasDeviceLogs… |
| `src/Scopes/` | Query scopes — TenantScope |
| `src/Http/Middleware/` | Shared middleware — AuthorizeTenantAccess, EnsureTenantDomain… |
| `src/Notifications/` | Platform notifications |
| `src/Observers/` | Eloquent observers |
| `src/Workflows/` | Workflow action executors |
| `database/migrations/` | All platform migrations — the canonical database schema |
| `database/seeders/` | All platform seeders — AdminSeeder, TenantSeeder, DeviceTypeSeeder… |
| `database/factories/` | Model factories for testing |

---

## `TenantApiKey` — machine auth for server-tenant

Every `server-tenant` instance (hosted or on-premise) authenticates to the central `app/`
API with a **tenant API key**. This model lives here because the key is a central platform
concept, not a portal concept.

**Model:** `TrackAnyDevice\Core\Models\TenantApiKey`
**Table:** `tenant_api_keys`

```php
Schema::create('tenant_api_keys', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('key_hash');          // bcrypt hash — never store plain
    $table->string('name')->default('Default'); // label for Filament display
    $table->timestamp('last_used_at')->nullable();
    $table->timestamps();
});
```

**Generation:** A key is created automatically when a tenant is approved in Filament
(`TenantObserver::approved()` or a Filament action). The plain key is shown once via
a Filament notification. After that only the hash exists.

**Validation:** `ValidateTenantApiKey` middleware (in `app/`) does:
```php
TenantApiKey::where('tenant_id', ...)->get()
  ->first(fn($k) => Hash::check($rawKey, $k->key_hash));
```

**Rules:**
- One tenant can have multiple keys (rotation without downtime)
- Revoked by deleting the `TenantApiKey` record in Filament
- `last_used_at` updated on every validated request (throttled — max once per minute)

---

## Rule 2 — Breaking changes require a major or minor version bump

This package is consumed by all server apps with tight version constraints (e.g. `^0.3.0`).
A breaking change (renamed model, removed column, changed event constructor, removed enum case)
must be:

1. Documented in `CHANGELOG.md` under `### Breaking Changes`
2. Released as a new tag (`v0.x.y` → bump x for breaking in `0.x`)
3. Communicated via a GitHub issue filed against each downstream server app repo

Never silently break a public API. If you rename a migration column or change a model's
`$fillable`, every server app that uses that model is affected.

---

## Rule 3 — Tenant scoping is automatic via `BelongsToTenant`

Models that belong to a tenant must use the `BelongsToTenant` trait. This adds `TenantScope`
automatically so queries inside a tenant request context are always scoped:

```php
class Beat extends Model
{
    use BelongsToTenant;
    // Beat::all() inside a tenant request → WHERE tenant_id = {active_tenant}
    // Beat::all() in CLI/admin → no scope applied
}
```

Never add manual `WHERE tenant_id = ?` clauses to queries — the trait handles it.
Never remove `BelongsToTenant` from a model that is tenant-owned.

---

## Rule 4 — `SignalService` is the only path for recording device telemetry

All inbound device signals (location, battery, sensor) must go through `SignalService::record()`.
Do not write directly to `device_locations` or update `Device` snapshot columns manually.
`SignalService` handles:
- MySQL device snapshot update (`last_lat`, `last_lon`, `battery_percent`, etc.)
- InfluxDB write (if enabled)
- `SignalCreatedEvent` dispatch (triggers alarm processing, beat violation checks)
- Critical signal routing (SOS, LowBattery, GeofenceExit → immediate broadcast)
- Routine signal buffering (debounced via `SignalBroadcastBuffer`)

---

## Rule 5 — Migrations are additive only in patch releases

In a patch release (`v0.x.y` → `v0.x.y+1`), migrations must only ADD columns or tables.
Never drop a column, rename a column, or change a column type in a patch.
Destructive migrations require a minor or major version bump.

---

## Dependencies

```json
"require": {
    "php": "^8.3",
    "laravel/framework": "^13.7",
    "laravel/fortify": "^1.0",
    "laravel/sanctum": "^4.0",
    "stancl/tenancy": "^3.10",
    "track-any-device/sms-gateway": "^1.0"
}
```

`sms-gateway` is the only TAD package dependency. Never add a dependency on a downstream
package (admin, drivers, jt808, sso-server, sso-client, mcp) — that would create a circular
dependency.

---

## Versioning

Tags are created automatically by GitHub Actions on every merge to `main`
(via `mathieudutour/github-tag-action`). Default bump is `patch`.

To trigger a minor bump: include `#minor` in the commit message.
To trigger a major bump: include `#major` in the commit message.

Always update `CHANGELOG.md` before merging a release PR.
