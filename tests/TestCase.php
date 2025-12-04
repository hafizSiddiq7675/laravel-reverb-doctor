<?php

namespace Bitsoftsolutions\LaravelReverbDoctor\Tests;

use Bitsoftsolutions\LaravelReverbDoctor\ReverbDoctorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ReverbDoctorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default environment for testing
        $app['config']->set('broadcasting.default', 'log');
    }
}
