<?php

namespace Mrluke\Bus;

use Illuminate\Support\ServiceProvider;
use Mrluke\Configuration\Host;
use Mrluke\Configuration\Schema;

use Mrluke\Bus\Contracts\Config;

final class BusServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([__DIR__ . '/../config/bus.php' => config_path('bus.php')], 'config');
        $this->publishes(
            [__DIR__ . '/../database/migrations/' => database_path('migrations')],
            'migrations'
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/bus.php', 'bus');

        $this->app->singleton(
            Config::class,
            function($app) {
                $schema = Schema::createFromFile(
                    __DIR__ . '/../config/schema.json',
                    true
                );

                return new Host(
                    $app['config']->get('bus'),
                    $schema
                );
            }
        );
    }
}
