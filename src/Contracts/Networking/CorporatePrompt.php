<?php

namespace LaravelNeuro\Contracts\Networking;

/**
 * Defines the contract for prompts that are compatible with LaravelNeuro
 * Networking features. Implementers must provide methods to encode the prompt
 * into a JSON representation and decode a JSON string back into a prompt instance.
 *
 * @package LaravelNeuro
 */
interface CorporatePrompt
{
    /**
     * Encodes the prompt into a JSON string.
     *
     * This method should convert the prompt data into a JSON-encoded string,
     * suitable for storage or transmission.
     *
     * @return string JSON-encoded representation of the prompt.
     */
    public function promptEncode();

    /**
     * Decodes a JSON-encoded prompt string back into a prompt instance.
     *
     * This static method takes a JSON-encoded string and converts it back
     * into an instance of a prompt that implements this contract.
     *
     * @param string $promptString The JSON-encoded prompt string.
     * @return static An instance of the prompt.
     */
    public static function promptDecode(string $promptString);
}