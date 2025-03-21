<?php
namespace LaravelNeuro\Prompts;

use Illuminate\Support\Collection;
use LaravelNeuro\Contracts\Networking\CorporatePrompt;

/**
 * A basic implementation of a prompt that extends Laravel's Collection and implements the CorporatePrompt Contract. 
 * 
 * This class provides methods to encode prompt data into JSON and decode JSON 
 * strings back into prompt data, as well as a method to set the prompt text.
 *
 * @package LaravelNeuro
 */
class BasicPrompt extends Collection implements CorporatePrompt {

    /**
     * Encodes the prompt data into a JSON string.
     *
     * This method utilizes the internal encoder to transform the prompt 
     * stored in the collection into a JSON representation.
     *
     * @return string JSON-encoded prompt data.
     */
    public function promptEncode() : string
    {
        $promptString = $this->encoder();
        return $promptString;
    }

    /**
     * Decodes a JSON-encoded prompt string into a BasicPrompt instance.
     *
     * This static method creates a new instance of BasicPrompt, decodes the provided 
     * JSON string, and sets the prompt data accordingly.
     *
     * @param string $promptString JSON-encoded prompt string.
     * @return static A new instance of BasicPrompt with the decoded prompt data.
     */
    public static function promptDecode(string $promptString)
    {
        $prompt = new static();
        $prompt->decoder($promptString);
        return $prompt;
    }

    /**
     * Sets the prompt text.
     *
     * Stores the provided prompt text in the collection under the key "prompt".
     *
     * @param string $prompt The prompt text.
     * @return $this Returns the current instance for method chaining.
     */
    public function setPrompt(string $prompt)
    {
        $this->put('prompt', $prompt);
        return $this;
    }

    /**
     * Gets the prompt text.
     *
     * @return string $this Returns the current prompt string.
     */
    public function getPrompt()
    {
        return $this->get('prompt');
    }

    /**
     * Encodes the prompt stored in the collection into a JSON string.
     *
     * Ensures that a "prompt" key exists in the collection before encoding.
     *
     * @return string The JSON-encoded representation of the prompt.
     */
    protected function encoder() : string
    {
        if (!$this->has('prompt')) {
            $this->set('prompt', '');
        }
        return json_encode($this->get('prompt'));
    }

    /**
     * Decodes a JSON-encoded prompt string and stores the result in the collection.
     *
     * The decoded value is stored under the "prompt" key.
     *
     * @param string $promptString The JSON-encoded prompt string.
     * @return void
     */
    protected function decoder(string $promptString)
    {
        $this->set('prompt', json_decode($promptString));
    }
}
