<?php

namespace TrackAnyDevice\Core;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;
use TrackAnyDevice\Core\Console\Commands\DetectOfflineDevices;
use TrackAnyDevice\Core\Console\Commands\NormalizeBeatsToPolygon;
use TrackAnyDevice\Core\Console\Commands\PollSmsInbox;
use TrackAnyDevice\Core\Console\Commands\PruneExpiredOtps;
use TrackAnyDevice\Core\Console\Commands\RunScheduledWorkflowsCommand;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (in_array(config('app.surface', 'core'), ['core', null], true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                DetectOfflineDevices::class,
                NormalizeBeatsToPolygon::class,
                PollSmsInbox::class,
                PruneExpiredOtps::class,
                RunScheduledWorkflowsCommand::class,
            ]);
        }

        Factory::guessFactoryNamesUsing(function (string $modelName) {
            return 'TrackAnyDevice\\Core\\Database\\Factories\\'.class_basename($modelName).'Factory';
        });
    }
}
