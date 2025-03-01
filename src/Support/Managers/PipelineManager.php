<?php

namespace LaravelNeuro\Support\Managers;

use LaravelNeuro\Support\Contracts\PipelineManagerContract;
use LaravelNeuro\Contracts\AiModel\Driver;
use LaravelNeuro\Drivers\WebRequest\GuzzleDriver;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Pipelines\OpenAI\ChatCompletion;
use LaravelNeuro\Pipelines\OpenAI\AudioTTS;
use LaravelNeuro\Pipelines\OpenAI\DallE;
use LaravelNeuro\Pipelines\OpenAI\Whisper;
use LaravelNeuro\Prompts\BasicPrompt;
use LaravelNeuro\Prompts\SUAprompt;
use LaravelNeuro\Prompts\IVFSprompt;
use LaravelNeuro\Prompts\PNSQFprompt;
use LaravelNeuro\Prompts\FSprompt;
use Illuminate\Support\Facades\Config;

class PipelineManager implements PipelineManagerContract
{
    protected Pipeline $pipeline;
    protected BasicPrompt $prompt;
    
    public function chatCompletion(?string $model = null, ?Driver $driver = null): PipelineManagerContract
    {
        return $this->setPipeline(ChatCompletion::class, SUAprompt::class, $model ?? Config::get('laravelneuro.models.default.chat'), $driver);
    }

    public function textToSpeech(?string $model = null, ?Driver $driver = null): PipelineManagerContract
    {
        return $this->setPipeline(AudioTTS::class, IVFSprompt::class, $model ?? Config::get('laravelneuro.models.default.tts'), $driver);
    }

    public function generateImage(?string $model = null, ?Driver $driver = null): PipelineManagerContract
    {
        return $this->setPipeline(DallE::class, PNSQFprompt::class, $model ?? Config::get('laravelneuro.models.default.image'), $driver);
    }

    public function speechToText(?string $model = null, ?Driver $driver = null): PipelineManagerContract
    {
        return $this->setPipeline(Whisper::class, FSprompt::class, $model ?? Config::get('laravelneuro.models.default.stt'), $driver);
    }

    protected function setPipeline(string $pipelineClass, string $promptClass, string $model, ?Driver $driver = null): PipelineManagerContract
    {
        $driver = $driver ?? new GuzzleDriver();
        $this->pipeline = new $pipelineClass($driver);
        $this->pipeline->setModel($model);
        $this->prompt = new $promptClass();
        return $this;
    }

    public function connection(): Pipeline
    {
        $this->pipeline->setPrompt($this->prompt);
        return $this->pipeline;
    }

    public function prompt(): BasicPrompt
    {
        return $this->prompt;
    }
}