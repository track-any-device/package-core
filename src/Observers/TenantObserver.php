<?php

declare(strict_types=1);

namespace TrackAnyDevice\Core\Observers;

use TrackAnyDevice\Core\Models\Tenant;
use TrackAnyDevice\Core\Models\TenantApiKey;
use TrackAnyDevice\Core\Models\TenantStatus;

/**
 * Reacts to Tenant lifecycle events.
 *
 * On approval: auto-generates the first machine API key for the tenant's
 * server-tenant portal. The plain key is logged so central staff can retrieve
 * it from application logs immediately after approval. Filament surfaces this
 * via the TenantResource approval action.
 */
class TenantObserver
{
    /**
     * Fires after any Tenant update. We only act when the status field
     * transitions specifically to Approved — not on repeated saves of an
     * already-approved tenant.
     */
    public function updated(Tenant $tenant): void
    {
        if (
            $tenant->wasChanged('status') &&
            $tenant->status === TenantStatus::Approved
        ) {
            $this->issueDefaultApiKey($tenant);
        }
    }

    private function issueDefaultApiKey(Tenant $tenant): void
    {
        // Guard: skip if the tenant already has a key (e.g. re-approval after
        // a rejected state) to avoid accumulating duplicate default keys.
        if ($tenant->apiKeys()->exists()) {
            return;
        }

        ['plain_key' => $plainKey] = TenantApiKey::generate($tenant, 'Default');

        // Log the plain key at WARNING level so it surfaces in log aggregators
        // and cannot be silently lost. Central staff must copy it before the
        // log rotates. The Filament approval action also surfaces it as a
        // one-time flash notification.
        logger()->warning('Tenant API key generated — copy now, not stored again', [
            'tenant_id'   => $tenant->id,
            'tenant_slug' => $tenant->slug,
            'plain_key'   => $plainKey,
        ]);
    }
}
