<?php

namespace Tests;

use Orchestra\Testbench\TestCase;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

require_once __DIR__.'/../vendor/autoload.php';

class PackageTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            'LaravelNeuro\\LaravelNeuro\\Providers\\LaravelNeuroServiceProvider',
        ];
    }

    // You can also set up other common configurations here, like environment setup
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__.'/../src/Networking/Database/migrations');
    }

    protected function tearDown(): void
    {
        if (File::exists(app_path('Corporations'))) {
            File::deleteDirectory(app_path('Corporations'));
        }
        if (File::exists(storage_path('app/LaravelNeuro'))) {
            File::deleteDirectory(storage_path('app/LaravelNeuro'));
        }

        parent::tearDown();
    }
}