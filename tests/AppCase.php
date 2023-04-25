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

        $this->loadLaravelMigrations();

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
        $app['path.base'] = __DIR__ . '/..';
        $app['config']->set('database.default', 'sqlite');
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
