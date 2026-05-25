<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Enums\Role;
use TrackAnyDevice\Core\Models\Domain;
use TrackAnyDevice\Core\Models\Tenant;
use TrackAnyDevice\Core\Models\TenantStatus;
use TrackAnyDevice\Core\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the "Pak-Track" dev tenant:
 *   - Slug:    pak-track
 *   - Domain:  tenant.{APP_DOMAIN}   (default: tenant.track-any-device.com)
 *   - User:    tenant@fleet.local / changeme  (Role::TenantUser)
 *   - OAuth client auto-created by TenantObserver on first create.
 *
 * Safe to run multiple times — all writes use updateOrCreate / firstOrCreate.
 */
class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Tenant ────────────────────────────────────────────────────────
        $tenant = Tenant::updateOrCreate(
            ['slug' => 'pak-track'],
            [
                'name'        => 'Pak-Track',
                'app_name'    => 'Pak-Track Fleet Portal',
                'type'        => 'portal',
                'status'      => TenantStatus::Approved,
                'approved_at' => now(),
                'metadata'    => [
                    'country' => 'Pakistan',
                    'env'     => 'dev',
                ],
            ]
        );

        // ── 2. Domain ────────────────────────────────────────────────────────
        $appDomain = env('APP_DOMAIN', 'track-any-device.com');
        $hostname  = "tenant.{$appDomain}";

        Domain::firstOrCreate(
            ['domain' => $hostname],
            ['tenant_id' => $tenant->id, 'is_primary' => true]
        );

        // ── 3. Tenant user ───────────────────────────────────────────────────
        $user = User::updateOrCreate(
            ['email' => env('TENANT_USER_EMAIL', 'tenant@fleet.local')],
            [
                'name'              => env('TENANT_USER_NAME', 'Pak-Track User'),
                'password'          => Hash::make(env('TENANT_USER_PASSWORD', 'changeme')),
                'role'              => Role::TenantUser,
                'email_verified_at' => now(),
            ]
        );

        if (! $tenant->users()->where('users.id', $user->id)->exists()) {
            $tenant->users()->attach($user->id);
        }

        // ── 4. Log OAuth client credentials ─────────────────────────────────
        // oauth_clients lives in server-sso; the table may not exist when
        // running migrations/seeds against package-core alone.
        if (\Illuminate\Support\Facades\Schema::hasTable('oauth_clients')) {
            $client = DB::table('oauth_clients')->where('tenant_id', $tenant->id)->first();
            if ($client) {
                $this->command->info("Tenant OAuth client: {$client->client_id}");
                $uris = json_decode($client->redirect_uris ?? '[]', true);
                $this->command->line("  Redirect URI: " . ($uris[0] ?? 'none'));
            } else {
                $this->command->warn('No OAuth client found — TenantObserver may not have fired.');
            }
        }

        $this->command->info("Tenant seeded: {$tenant->name} (slug: {$tenant->slug})");
        $this->command->line("  Domain: https://{$hostname}/");
        $this->command->line("  User:   {$user->email} / changeme");
    }
}
