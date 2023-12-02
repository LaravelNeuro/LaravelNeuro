<?php

namespace Kbirenheide\L3MA\Providers;

use Illuminate\Support\ServiceProvider;

Class L3MAServiceProvider extends ServiceProvider {

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Kbirenheide\L3MA\Console\Commands\MakeLLMpipeline::class,
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