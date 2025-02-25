<?php

namespace LaravelNeuro\Contracts\AiModel;

use Generator;

/**
 * Interface Driver
 *
 * Defines the required methods for Ai Model Drivers, which are attached to 
 * Pipelines via dependency injection. The default driver is the WebRequest GuzzleDriver
 * 
 * @package LaravelNeuro
 */
interface Driver
{
   /**
     * Sets the model identifier for the driver.
     *
     * @param mixed $model The model identifier.
     * @return self
     */
    public function setModel($model): self;

    /**
     * Retrieves the current model identifier.
     *
     * @return string The model identifier.
     */
    public function getModel(): string;

    /**
     * Sets the system prompt.
     *
     * This can be used to provide context or instructions to the AI model.
     *
     * @param mixed $system The system prompt.
     * @return self
     */
    public function setSystemPrompt($system): self;

    /**
     * Retrieves the current system prompt.
     *
     * @return mixed The system prompt.
     */
    public function getSystemPrompt();

    /**
     * Sets the prompt for the driver.
     *
     * @param mixed $prompt The prompt value.
     * @return self
     */
    public function setPrompt($prompt): self;

    /**
     * Retrieves the current prompt.
     *
     * @return mixed The current prompt value.
     */
    public function getPrompt();

    /**
     * Executes the API request and returns the response body.
     *
     * This method should internally call the connect() method and handle
     * any necessary request execution logic.
     *
     * @return mixed The response body.
     */
    public function output();

    /**
     * Executes a streaming API request.
     *
     * Enables streaming mode and returns a generator that yields JSON-encoded
     * data chunks from the API response. This is useful for handling responses
     * that include large or streaming payloads.
     *
     * @return Generator Yields JSON-encoded data chunks.
     */
    public function stream(): Generator;
}
