<?php

namespace LaravelNeuro\Contracts;

interface PipelineContract 
{
    /**
     * Sets the model for the pipeline.
     *
     * This method assigns the given model to the pipeline and updates the request payload accordingly.
     *
     * @param mixed $model The model identifier to be used.
     * @return self
     */
    public function setModel($model): self;

    /**
     * Sets the prompt for the pipeline.
     *
     * This method accepts either a string or an instance of SUAPrompt. If a SUAPrompt instance is provided,
     * it iterates over the prompt elements to build the complete prompt and system message.
     *
     * @param string|SUAPrompt $prompt The prompt text or SUAPrompt instance.
     * @return self
     * @throws \InvalidArgumentException If the provided prompt is neither a string nor an instance of SUAPrompt.
     */
    public function setPrompt($prompt): self;

    /**
     * Retrieves the current model.
     *
     * @return mixed The current model identifier.
     */
    public function getModel();

    /**
     * Retrieves the current prompt.
     *
     * @return string The current prompt text.
     */
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
    public function stream(): \Generator;
}
