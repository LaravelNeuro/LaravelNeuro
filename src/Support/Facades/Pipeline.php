<?php

namespace LaravelNeuro\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Pipeline extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravelneuro.pipeline'; // Must match the service binding in LaravelNeuroServiceProvider
    }
}
