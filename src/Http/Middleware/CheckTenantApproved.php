<?php

namespace TrackAnyDevice\Core\Http\Middleware;

use TrackAnyDevice\Core\Models\Tenant;
use TrackAnyDevice\Core\Models\TenantStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block access to tenant subdomains whose Tenant row is not yet approved.
 *
 * Runs AFTER InitializeTenancyByDomain — by the time we reach this middleware
 * the active tenant is bound to `tenancy()->tenant`. The tenants table is
 * the source of truth for approval status.
 *
 * Central staff (admin/supervisor/staff) bypass this gate and can always
 * access pending tenant domains for diagnostic purposes.
 */
class CheckTenantApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! function_exists('tenancy') || ! tenancy()->tenant) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && $user->role?->isCentralStaff()) {
            return $next($request);
        }

        $tenant = Tenant::find(tenancy()->tenant->getKey());

        if (! $tenant || $tenant->status !== TenantStatus::Approved) {
            return response()->view('tenancy.pending', [
                'tenant_name' => $tenant?->name ?? tenancy()->tenant->slug,
            ], 403);
        }

        return $next($request);
    }
}
