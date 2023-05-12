<?php

namespace Mrluke\Bus;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Log\Logger;
use Illuminate\Support\ServiceProvider;
use Mrluke\Configuration\Host;
use Mrluke\Configuration\Schema;

use Mrluke\Bus\Contracts\CommandBus;
use Mrluke\Bus\Contracts\Config;
use Mrluke\Bus\Contracts\ProcessRepository;

/**
 * Class BusServiceProvider
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus
 *
 * @property Container $app
 * @codeCoverageIgnore
 */
final class BusServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ .'/../database/migrations');

        $this->publishes([__DIR__ .'/../config/bus.php' => config_path('bus.php')], 'config');
        $this->publishes(
            [__DIR__ .'/../database/migrations/' => database_path('migrations')],
            'migrations'
        );

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'bus');

        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/bus'),
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ .'/../config/bus.php', 'bus');

        /*
         * Async Handler Job's handle method binding.
         *
         */
        $this->app->bindMethod(
            AsyncHandlerJob::class .'@handle',
            function($job, $app) {
                return $job->handle(
                    $app->make(ProcessRepository::class),
                    $app->make(Container::class),
                    $app->make(Logger::class)
                );
            }
        );

        /*
         * Command Bus binding.
         */
        $this->app->singleton(
            CommandBus::class,
            function($app) {
                /* @var \Illuminate\Foundation\Application $app */
                $container = $app->make(Container::class);

                return new \Mrluke\Bus\CommandBus(
                    $app->make(ProcessRepository::class),
                    $container,
                    $app->make(Logger::class),
                    function($connection = null) use ($app) {
                        return $app->make(Factory::class)->connection($connection);
                    }
                );
            }
        );

        /*
         * Config binding.
         */
        $this->app->singleton(
            Config::class,
            function($app) {
                $schema = Schema::createFromFile(
                    __DIR__ .'/../config/schema.json',
                    true
                );

                return new Host(
                    $app['config']->get('bus'),
                    $schema
                );
            }
        );

        /*
         * Process Repository binding.
         */
        $this->app->singleton(
            ProcessRepository::class,
            function($app) {
                $config = $app->make(Config::class);

                return new DatabaseProcessRepository(
                    $config,
                    $app->make('db')->connection($config->get('database')),
                    $app->make('auth')->guard()
                );
            }
        );
    }
}
