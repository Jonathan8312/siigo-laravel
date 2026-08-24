<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests;

use Illuminate\Foundation\Application;
use Jonathan8312\Siigo\SiigoServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [SiigoServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        /** @var Application $app */
        $app['config']->set('siigo.username', 'default-testing-username');
        $app['config']->set('siigo.access_key', 'default-testing-access-key');
        $app['config']->set('siigo.partner_id', 'TestingPartner');
        $app['config']->set('siigo.base_url', 'https://siigo.test');
        $app['config']->set('siigo.cache.token_safety_margin_seconds', 60);
    }

    /**
     * Typed accessor for the booted testing application, narrowing away
     * the nullability of the base Testbench TestCase's $app property
     * (which is only null before setUp() runs).
     */
    protected function app(): Application
    {
        if (! $this->app instanceof Application) {
            throw new \RuntimeException('The testing application has not been booted yet.');
        }

        return $this->app;
    }
}
