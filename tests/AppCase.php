<?php

namespace Tests;

use Orchestra\Testbench\TestCase;

use Mrluke\Bus\BusServiceProvider;

/**
 * Feature AppCase for package.
 *
 * @author    Łukasz Sitnicki (mr-luke)
 * @link      http://github.com/mr-luke/framekit
 * @license   MIT
 */
class AppCase extends TestCase
{
    /**
     * Setup TestCase.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations(['--database' => 'mysql']);

        $this->artisan('migrate')->run();
    }

    /**
     * Get application timezone.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return string|null
     */
    protected function getApplicationTimezone($app)
    {
        return 'Europe/Warsaw';
    }

    /**
     * Setting environment for Test.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['path.base'] = __DIR__.'/..';
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST'),
            'database'  => env('DB_NAME'),
            'username'  => env('DB_USER'),
            'password'  => env('DB_PASS'),
            'prefix'    => env('DB_PREFIX'),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'strict'    => true,
        ]);
    }

    /**
     * Return array of providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [BusServiceProvider::class];
    }
}
