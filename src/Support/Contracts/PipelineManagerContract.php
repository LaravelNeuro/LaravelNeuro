<?php

namespace LaravelNeuro\Support\Contracts;

use LaravelNeuro\Contracts\AiModel\Driver;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Prompts\BasicPrompt;

interface PipelineManagerContract
{
    public function chatCompletion(?string $model = null, ?Driver $driver = null): PipelineManagerContract;
    public function textToSpeech(?string $model = null, ?Driver $driver = null): PipelineManagerContract;
    public function generateImage(?string $model = null, ?Driver $driver = null): PipelineManagerContract;
    public function speechToText(?string $model = null, ?Driver $driver = null): PipelineManagerContract;

    public function connection(): Pipeline; // Returns the selected pipeline instance
    public function prompt(): BasicPrompt; // Returns the corresponding prompt class
}
