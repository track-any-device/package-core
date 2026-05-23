---
name: tad-tenant-setup
description: Create and configure TAD tenants — covering tenant creation, user membership, approval flow, registration gating, SSO middleware, and interface modes. Use when working with the Tenant model, tenant_users pivot, AuthorizeTenantAccess, or multi-tenant routing.
---

# TAD Tenant Setup

## Tenant model

The `Tenant` model extends stancl's `BaseTenant` with a **bigint auto-incrementing PK** (not a UUID). All tenant-owned data (devices, beats, assignees, incidents) lives in the central database with a `tenant_id` column — there are no per-tenant databases.

Stancl stores unknown attributes in a JSON `data` column. Only columns listed in `getCustomColumns()` are real database columns. Always use real columns — never rely on the virtual `data` bag for runtime queries.

## Creating a tenant

```php
use TrackAnyDevice\Core\Models\Tenant;
use TrackAnyDevice\Core\Models\TenantStatus;

$tenant = Tenant::create([
    'name'   => 'Acme Logistics',
    'slug'   => 'acme',
    'type'   => 'fleet',
    'status' => TenantStatus::Pending,
    'registration_enabled' => false,
]);

// Add the primary domain
$tenant->domains()->create(['domain' => 'acme.example.com']);
```

`TenantObserver` (from `track-any-device/sso-server`) automatically provisions an OAuthClient row when the tenant is created. Tenants created before that observer was added won't have one — handle this case with a `500` abort in `AuthorizeTenantAccess`.

## Tenant status

| Status | Description |
|---|---|
| `pending` | Just created — not yet visible to tenant users |
| `approved` | Active — tenant users can log in |
| `suspended` | Blocked — `CheckTenantApproved` middleware returns 403 |

Approve a tenant:

```php
$tenant->update([
    'status'      => TenantStatus::Approved,
    'approved_at' => now(),
]);
```

## Adding users

Membership is stored in the `tenant_users` pivot (central DB). Admins and staff are NOT in this pivot — their access is role-based.

```php
// Add a user
$tenant->users()->attach($user->id);

// Remove a user
$tenant->users()->detach($user->id);

// Check membership
$isMember = $tenant->users()->where('user_id', $user->id)->exists();
```

## Interface modes

| Mode | Constant | Description |
|---|---|---|
| `default` | `Tenant::INTERFACE_DEFAULT` | Standard org/fleet UI |
| `no_org` | `Tenant::INTERFACE_NO_ORG` | Simplified UI — no organisational structure |

```php
$tenant->update(['interface_mode' => Tenant::INTERFACE_NO_ORG]);

if ($tenant->isNoOrgMode()) {
    // Render simplified layout
}
```

## Controlling self-registration

```php
// Allow new users to self-register on this tenant's subdomain
$tenant->update(['registration_enabled' => true]);

// Block it
$tenant->update(['registration_enabled' => false]);
```

`GateTenantRegistration` middleware enforces this on the `/register` route. `AuthorizeTenantAccess` exempts `/register` from the OAuth bounce so the registration page remains reachable.

## Middleware stack for tenant routes

Apply in this order in `routes/tenant.php` or the route group:

```php
Route::middleware([
    'web',
    \TrackAnyDevice\Core\Http\Middleware\InitializeTenancyForRequest::class,
    \TrackAnyDevice\Core\Http\Middleware\CheckTenantApproved::class,
    \TrackAnyDevice\Core\Http\Middleware\AuthorizeTenantAccess::class,
])->group(function () {
    // Tenant routes here
});
```

## Authorising tenant access in controllers/policies

A `access-tenant` gate mirrors `AuthorizeTenantAccess`:

```php
// In a controller
$this->authorize('access-tenant', tenancy()->tenant);

// In a policy
Gate::allows('access-tenant', $tenant);
```

## Tenant logo

```php
// Store a logo
$tenant->update(['logo_path' => $request->file('logo')->store('tenant-logos', 'public')]);

// Get a public URL
$url = $tenant->logoUrl(); // null if no logo set
```

## See also

- `references/sso-flow.md` — OAuth SSO login flow for tenant subdomains
