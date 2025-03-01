<?php

namespace LaravelNeuro\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \LaravelNeuro\Support\Managers\PipelineManager chatCompletion(?string $model = null, ?Driver $driver = null)
 * @method static \LaravelNeuro\Support\Managers\PipelineManager textToSpeech(?string $model = null, ?Driver $driver = null)
 * @method static \LaravelNeuro\Support\Managers\PipelineManager generateImage(?string $model = null, ?Driver $driver = null)
 * @method static \LaravelNeuro\Support\Managers\PipelineManager speechToText(?string $model = null, ?Driver $driver = null)
 * @method static \LaravelNeuro\Contracts\AiModel\Pipeline connection() // Returns the currently selected Pipeline instance
 * @method static \LaravelNeuro\Prompts\BasicPrompt prompt() // Returns the associated Prompt instance
 *
 * @see \LaravelNeuro\Support\Managers\PipelineManager
 *
 * @package LaravelNeuro
 */
class Pipeline extends Facade
{
    /**
     * Get the registered name of the component in the service container.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravelneuro.pipeline'; // Must match the service binding in LaravelNeuroServiceProvider
    }
}