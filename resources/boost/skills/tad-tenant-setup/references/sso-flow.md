# Tenant SSO Login Flow

Tenant subdomains use OAuth 2.0 SSO via the central identity server (`login.*`). Sessions are per-subdomain — there is no shared cookie.

## Flow overview

```
1. User visits tenant.example.com/dashboard (unauthenticated)
2. AuthorizeTenantAccess detects no session
3. Redirects to: login.example.com/oauth/authorize
      ?client_id=<tenant_oauth_client_id>
      &redirect_uri=https://tenant.example.com/sso/callback
      &state=<random 40-char nonce>
4. login.* authenticates the user (or skips if already logged in there)
5. login.* mints a one-time SsoToken and redirects to:
      tenant.example.com/sso/callback?token=<token>&state=<nonce>
6. SsoCallbackController validates token, calls Auth::login($user)
7. User is redirected to their original target
```

## Config required

| Key | Description |
|---|---|
| `APP_LOGIN_DOMAIN` | Hostname of the identity server (e.g. `login.example.com`) |
| `APP_DOMAIN` | Central domain — fallback when `APP_LOGIN_DOMAIN` is unset |

## Exempt paths

`AuthorizeTenantAccess` does NOT bounce these paths through OAuth — they are entry points:

- `/register` — self-registration (gated separately by `GateTenantRegistration`)
- `/sso/callback` — consumes the one-time token from the identity server

## OAuthClient provisioning

Each tenant gets one `OAuthClient` row (created by `TenantObserver`). If missing, `AuthorizeTenantAccess` aborts with 500. Fix:

```php
// Manually provision an OAuth client for a legacy tenant
$client = \TrackAnyDevice\SsoServer\Models\OAuthClient::create([
    'tenant_id'    => $tenant->id,
    'is_active'    => true,
    'redirect_uris' => ['https://tenant.example.com/sso/callback'],
]);
```

## SMS 2FA (post-login)

After OAuth login, `RequireSmsChallenge` middleware (if registered on tenant routes) challenges the user for an SMS OTP. The challenge is marked in the session as `sms_2fa_verified`. It is cleared on explicit logout via the `Logout` event listener in `AppServiceProvider`.
