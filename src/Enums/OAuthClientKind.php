<?php

namespace TrackAnyDevice\Core\Enums;

enum OAuthClientKind: string
{
    /**
     * The public marketing / web site + docs at track-any-device.com.
     */
    case Web = 'web';

    /**
     * The end-user app at my.track-any-device.com (orders, devices, tenant
     * picker). Singleton — provisioned from MY_CLIENT_ID / MY_CLIENT_SECRET.
     */
    case My = 'my';

    /**
     * The Filament admin panel at admin.track-any-device.com.
     * Singleton — provisioned from ADMIN_CLIENT_ID / ADMIN_CLIENT_SECRET.
     */
    case Admin = 'admin';

    /**
     * The GraphQL API explorer at graphql.track-any-device.com.
     * Central staff only (Admin, Supervisor, Staff roles).
     */
    case GraphQl = 'graphql';

    /**
     * One row per tenant. Auto-created by TenantObserver when a Tenant is
     * created; the plain client_secret is shown once at that moment.
     */
    case Tenant = 'tenant';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Web',
            self::My => 'My (End-user app)',
            self::Admin  => 'Admin panel',
            self::GraphQl => 'GraphQL Explorer',
            self::Tenant => 'Tenant',
        };
    }
}
