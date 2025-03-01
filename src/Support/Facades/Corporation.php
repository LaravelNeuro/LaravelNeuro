<?php

namespace LaravelNeuro\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Corporation extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravelneuro.corporation'; // Must match the service binding in LaravelNeuroServiceProvider
    }
}
