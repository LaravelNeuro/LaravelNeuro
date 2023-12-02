<?php

namespace Kbirenheide\L3MA\Providers;

use Illuminate\Support\ServiceProvider;

Class L3MA extends ServiceProvider {

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Kbirenheide\LaravelAi\Console\Commands\MakeLLMpipeline::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/l3ma.php' => config_path('l3ma.php'),
        ], 'l3ma-config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/l3ma.php', 'l3ma'
        );
    }

}