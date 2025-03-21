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

/**
 * The PipelineManager class provides a structured way to instantiate and manage AI pipelines.
 *
 * This manager offers quick access to different AI functionalities such as chat completion,
 * text-to-speech, image generation, and speech-to-text, ensuring modularity and ease of use.
 *
 * @package LaravelNeuro
 */
class PipelineManager implements PipelineManagerContract
{
    /**
     * The currently active pipeline instance.
     *
     * @var Pipeline
     */
    protected Pipeline $pipeline;

    /**
     * The associated prompt class instance.
     *
     * @var BasicPrompt
     */
    protected BasicPrompt $prompt;

    /**
     * Initializes a ChatCompletion pipeline.
     *
     * @param string|null $model The model name (e.g., "gpt-4-turbo-preview").
     * @param Driver|null $driver The driver instance to use (defaults to GuzzleDriver).
     * @return PipelineManagerContract Returns the current instance for method chaining.
     */
    public function chatCompletion(?string $model = null, ?Driver $driver = null): PipelineManagerContract
    {
        return $this->make(ChatCompletion::class, $model, $driver);
    }

    /**
     * Initializes a Text-to-Speech (TTS) pipeline.
     *
     * @param string|null $model The model name (e.g., "tts-1", "eleven-monolingual-v1").
     * @param Driver|null $driver The driver instance to use (defaults to GuzzleDriver).
     * @return PipelineManagerContract Returns the current instance for method chaining.
     */
    public function textToSpeech(?string $model = null, ?Driver $driver = null): PipelineManagerContract
    {
        return $this->make(AudioTTS::class, $model, $driver);
    }

    /**
     * Initializes an AI Image Generation pipeline.
     *
     * @param string|null $model The model name (e.g., "dall-e-3").
     * @param Driver|null $driver The driver instance to use (defaults to GuzzleDriver).
     * @return PipelineManagerContract Returns the current instance for method chaining.
     */
    public function generateImage(?string $model = null, ?Driver $driver = null): PipelineManagerContract
    {
        return $this->make(DallE::class, $model, $driver);
    }

    /**
     * Initializes a Speech-to-Text (STT) pipeline.
     *
     * @param string|null $model The model name (e.g., "whisper-1").
     * @param Driver|null $driver The driver instance to use (defaults to GuzzleDriver).
     * @return PipelineManagerContract Returns the current instance for method chaining.
     */
    public function speechToText(?string $model = null, ?Driver $driver = null): PipelineManagerContract
    {
        return $this->make(Whisper::class, $model, $driver);
    }

    /**
     * Initializes the provided pipeline.
     *
     * @param string $pipelineClass the fully qualified class name of the pipeline.
     * @param string|null $model The model name (e.g., "gpt-4-turbo-preview", defaults to the pipeline's default model).
     * @param Driver|null $driver The driver instance to use (defaults to the pipeline's default driver).
     * @return PipelineManagerContract Returns the current instance for method chaining.
     */
    public function make(string $pipelineClass, ?string $model = null, ?Driver $driver = null): PipelineManagerContract
    {
        $pipeline = new $pipelineClass;
        return $this->setPipeline(
            $pipelineClass,
            $pipeline->promptClass(),
            $model ?? $pipeline->getModel(),
            $driver ? new $driver : new ($pipeline->driverClass())
        );
    }

    /**
     * Configures a pipeline with a specified model and driver.
     *
     * @param string $pipelineClass The fully qualified class name of the pipeline.
     * @param string $promptClass The fully qualified class name of the prompt.
     * @param string $model The model to use within the pipeline.
     * @param Driver|null $driver The driver instance (defaults to GuzzleDriver).
     * @return PipelineManagerContract Returns the current instance for method chaining.
     */
    protected function setPipeline(string $pipelineClass, string $promptClass, string $model, ?Driver $driver = null): PipelineManagerContract
    {
        $driver = $driver ?? new GuzzleDriver();
        $this->pipeline = new $pipelineClass($driver);
        $this->pipeline->setModel($model);
        $this->prompt = new $promptClass();
        return $this;
    }

    /**
     * Retrieves the configured pipeline instance.
     *
     * Ensures that the associated prompt is injected into the pipeline before returning it.
     *
     * @return ChatCompletion|AudioTTS|DallE|Whisper The configured pipeline instance.
     *
     * @throws \Exception If no pipeline has been set before calling this method.
     */
    public function connection(): Pipeline
    {
        $this->pipeline->setPrompt($this->prompt);
        return $this->pipeline;
    }

    /**
     * Retrieves the associated prompt instance for the active pipeline.
     *
     * @return BasicPrompt The prompt class instance.
     *
     * @throws \Exception If no pipeline has been set before calling this method.
     */
    public function prompt(): BasicPrompt
    {
        return $this->prompt;
    }
}
