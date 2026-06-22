<?php

namespace TrackAnyDevice\Core;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;
use TrackAnyDevice\Core\Console\Commands\FlushSignalBroadcasts;
use TrackAnyDevice\Core\Console\Commands\NormalizeBeatsToPolygon;
use TrackAnyDevice\Core\Console\Commands\PruneExpiredOtps;
use TrackAnyDevice\Core\Console\Commands\RunScheduledWorkflowsCommand;
use TrackAnyDevice\Core\Models\Device;
use TrackAnyDevice\Core\Models\DeviceCommand;
use TrackAnyDevice\Core\Models\Tenant;
use TrackAnyDevice\Core\Observers\DeviceCommandObserver;
use TrackAnyDevice\Core\Observers\DeviceObserver;
use TrackAnyDevice\Core\Observers\TenantObserver;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/device_logs.php', 'device_logs');
    }

    public function boot(): void
    {
        if (in_array(config('app.surface', 'core'), ['core', null], true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        // Auto-generate a machine API key when a tenant is approved so the
        // server-tenant portal can authenticate to the central app/ REST API.
        // Skipped on the tenant surface — server-tenant has no admin context.
        Device::observe(DeviceObserver::class);
        DeviceCommand::observe(DeviceCommandObserver::class);

        if (config('app.surface') !== 'tenant') {
            Tenant::observe(TenantObserver::class);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                FlushSignalBroadcasts::class,
                NormalizeBeatsToPolygon::class,
                PruneExpiredOtps::class,
                RunScheduledWorkflowsCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/device_logs.php' => config_path('device_logs.php'),
            ], 'tad-core-config');
        }

        Factory::guessFactoryNamesUsing(function (string $modelName) {
            return 'TrackAnyDevice\\Core\\Database\\Factories\\'.class_basename($modelName).'Factory';
        });
    }
}
