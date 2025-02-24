<?php

namespace LaravelNeuro\Contracts;

use LaravelNeuro\Enums\RequestType;

interface ApiAdapterContract
{
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
