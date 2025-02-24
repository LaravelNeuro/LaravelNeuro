<?php

namespace LaravelNeuro\Contracts\AiModel;

use Generator;

/**
 * Contract Driver
 *
 * Defines the required methods for Ai Model Drivers, which are attached to 
 * Pipelines via dependency injection. The default driver is the WebRequest GuzzleDriver
 * 
 * @package LaravelNeuro
 */
interface Driver
{
    public function setModel($model): self;

    public function getModel();

    public function setSystemPrompt($system) : self;

    public function getSystemPrompt();

    public function setPrompt($prompt) : self;

    public function getPrompt();

    /**
     * Executes the API request and returns the response body.
     *
     * This method leverages the connect() method and retrieves the response body.
     *
     * @return mixed The response body.
     */
    public function output();

    /**
     * Executes a streaming API request.
     *
     * Enables streaming mode and returns a generator that yields JSON-encoded
     * data chunks from the API response. Useful for handling responses that
     * include large or streaming payloads.
     *
     * @return Generator Yields JSON-encoded data chunks.
     */
    public function stream(): Generator;
}
