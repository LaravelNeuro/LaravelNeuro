<?php

namespace Kbirenheide\LaravelNeuro\Providers;

use Illuminate\Support\ServiceProvider;

Class LaravelNeuroServiceProvider extends ServiceProvider {

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Kbirenheide\LaravelNeuro\Console\Commands\MakeLLMpipeline::class,
            ]);
        }

        $this->loadMigrationsFrom(__DIR__.'/../Networking/Database/migrations');

        $this->publishes([
            __DIR__.'/../config/laravelneuro.php' => config_path('laravelneuro.php'),
        ], 'laravelneuro-config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/laravelneuro.php', 'laravelneuro'
        );
    }

}