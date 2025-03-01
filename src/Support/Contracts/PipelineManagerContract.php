<?php

namespace LaravelNeuro\Support\Contracts;

use LaravelNeuro\Contracts\AiModel\Driver;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Prompts\BasicPrompt;

/**
 * The Contract for the CorporationManager, defining the required methods.
 *
 * Defines a contract for managing AI pipelines in LaravelNeuro. Provides
 * easy access to various AI functionalities such as chat completion, text-to-speech,
 * image generation, and speech-to-text via a structured and modular approach.
 *
 * @package LaravelNeuro
 */
interface PipelineManagerContract
{
    /**
     * Instantiates and configures an AI ChatCompletion pipeline.
     *
     * @param string|null $model The model name (e.g., "gpt-4-turbo-preview").
     * @param Driver|null $driver The driver instance to use (defaults to GuzzleDriver).
     * @return PipelineManagerContract Returns the current instance for method chaining.
     */
    public function chatCompletion(?string $model = null, ?Driver $driver = null): PipelineManagerContract;

    /**
     * Instantiates and configures a Text-to-Speech (TTS) pipeline.
     *
     * @param string|null $model The model name (e.g., "tts-1", "eleven-monolingual-v1").
     * @param Driver|null $driver The driver instance to use (defaults to GuzzleDriver).
     * @return PipelineManagerContract Returns the current instance for method chaining.
     */
    public function textToSpeech(?string $model = null, ?Driver $driver = null): PipelineManagerContract;

    /**
     * Instantiates and configures an AI Image Generation pipeline.
     *
     * @param string|null $model The model name (e.g., "dall-e-3").
     * @param Driver|null $driver The driver instance to use (defaults to GuzzleDriver).
     * @return PipelineManagerContract Returns the current instance for method chaining.
     */
    public function generateImage(?string $model = null, ?Driver $driver = null): PipelineManagerContract;

    /**
     * Instantiates and configures a Speech-to-Text (STT) pipeline.
     *
     * @param string|null $model The model name (e.g., "whisper-1").
     * @param Driver|null $driver The driver instance to use (defaults to GuzzleDriver).
     * @return PipelineManagerContract Returns the current instance for method chaining.
     */
    public function speechToText(?string $model = null, ?Driver $driver = null): PipelineManagerContract;

    /**
     * Retrieves the current pipeline instance after initialization.
     *
     * @return Pipeline The pipeline instance associated with the current selection.
     *
     * @throws \Exception If no pipeline has been selected before calling this method.
     */
    public function connection(): Pipeline;

    /**
     * Retrieves the corresponding prompt class instance for the selected pipeline.
     *
     * @return BasicPrompt The prompt class instance.
     *
     * @throws \Exception If no pipeline has been selected before calling this method.
     */
    public function prompt(): BasicPrompt;
}
