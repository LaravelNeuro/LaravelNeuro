<?php

namespace LaravelNeuro\LaravelNeuro\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

use LaravelNeuro\LaravelNeuro\Console\Commands\CorporationMakeMigration;

Class LaravelNeuroServiceProvider extends ServiceProvider {

    public function boot()
    {
        $this->app->singleton('command.lneuro.make-network-migration', function ($app) {
            $creator = $app['migration.creator'];
            $composer = $app['composer'];
        
            return new CorporationMakeMigration($creator, $composer);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                \LaravelNeuro\LaravelNeuro\Console\Commands\MakeLLMpipeline::class,
                \LaravelNeuro\LaravelNeuro\Console\Commands\IncorporateSetup::class,
                \LaravelNeuro\LaravelNeuro\Console\Commands\IncorporateInstall::class,
                \LaravelNeuro\LaravelNeuro\Console\Commands\CorporationRun::class,
                \LaravelNeuro\LaravelNeuro\Console\Commands\PackageSetup::class,
                \LaravelNeuro\LaravelNeuro\Console\Commands\CleanUp::class,
                'command.lneuro.make-network-migration',
            ]);
        }

        $this->createDirectoryIfNotExists(storage_path('app/LaravelNeuro'));

        $this->loadMigrationsFrom(__DIR__.'/../Networking/Database/migrations');

        $this->publishes([
            __DIR__.'/../Networking/Database/migrations' => database_path('migrations')
        ], 'migrations');

        $this->publishes([
            __DIR__.'/../config/laravelneuro.php' => config_path('laravelneuro.php'),
        ], 'laravelneuro-config');

    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/laravelneuro.php', 'laravelneuro'
        );
        
        $this->mergeConfigFrom(
            __DIR__.'/../config/filesystems.php', 'filesystems.disks'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../config/database.php', 'database.connections'
        );
    }

    protected function createDirectoryIfNotExists($path)
    {
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true); // true for recursive creation
        }
    }

}