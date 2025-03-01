<?php

namespace LaravelNeuro\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \LaravelNeuro\Support\Managers\CorporationManager mount(int|string $corporation)
 * @method static \LaravelNeuro\Support\Managers\CorporationManager task(string $task)
 * @method static \LaravelNeuro\Support\Managers\CorporationManager debug(bool $debug = true)
 * @method static \LaravelNeuro\Support\Managers\CorporationManager disableHistory()
 * @method static \LaravelNeuro\Support\Managers\CorporationManager enableHistory()
 * @method static \LaravelNeuro\Networking\Database\Models\NetworkCorporation getCorporationModel()
 * @method static \LaravelNeuro\Networking\Database\Models\NetworkProject run()
 *
 * @see \LaravelNeuro\Support\Managers\CorporationManager
 *
 * @package LaravelNeuro
 */
class Corporation extends Facade
{
    /**
     * Get the registered name of the component in the service container.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravelneuro.corporation'; // Must match the service binding in LaravelNeuroServiceProvider
    }
}
