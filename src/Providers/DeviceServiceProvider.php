<?php

namespace TrackAnyDevice\Core\Providers;

use TrackAnyDevice\Drivers\Connectors\SMSConnector;
use TrackAnyDevice\Drivers\Contracts\DeviceConnectorInterface;
use TrackAnyDevice\Drivers\AOT120Driver;
use TrackAnyDevice\Drivers\Contracts\DeviceDriverInterface;
use TrackAnyDevice\Drivers\GF07Driver;
use TrackAnyDevice\Jt808\Jt808Driver;
use TrackAnyDevice\Drivers\P901Driver;
use Illuminate\Support\ServiceProvider;

class DeviceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SMSConnector::class);
        $this->app->bind(DeviceConnectorInterface::class, SMSConnector::class);

        // Driver bindings keyed by DeviceType slug.
        // Resolve via: app(DeviceServiceProvider::driverFor($slug))
        $this->app->bind('device.driver.p901', P901Driver::class);
        $this->app->bind('device.driver.gf-07', GF07Driver::class);
        $this->app->bind('device.driver.jt808', Jt808Driver::class);
        $this->app->bind('device.driver.aot120', AOT120Driver::class);

        // TAD101 driver bindings are registered by Tad101ServiceProvider
        // (track-any-device/tad101 package).
    }

    /**
     * Resolve the driver for a given DeviceType slug.
     *
     * @throws \InvalidArgumentException if no driver is registered for the slug
     */
    public static function driverFor(string $slug): DeviceDriverInterface
    {
        $key = "device.driver.{$slug}";

        if (! app()->bound($key)) {
            throw new \InvalidArgumentException("No device driver registered for slug '{$slug}'.");
        }

        return app($key);
    }
}
