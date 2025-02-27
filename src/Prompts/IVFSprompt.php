<?php
namespace LaravelNeuro\Prompts;

use LaravelNeuro\Prompts\BasicPrompt;

/**
 * IVFSprompt stands for "Input, Voice, Format, Settings" and is designed for TTS (Text-to-Speech)
 * pipelines. It handles the construction and deconstruction of prompt data for TTS operations,
 * allowing configuration of input text, voice selection, output format, and additional settings.
 * The encoder builds a custom VFS block appended to the input, while the decoder extracts these
 * parameters from a JSON-encoded prompt.
 *
 * @package LaravelNeuro
 */
class IVFSprompt extends BasicPrompt {

    /**
     * IVFSprompt constructor.
     *
     * Initializes the prompt with default TTS parameters:
     * - input: "Say this sentence out loud."
     * - voice: null (no default voice)
     * - format: "mp3"
     * - settings: an empty array
     */
    public function __construct()
    {
        $this->put('input', 'Say this sentence out loud.');
        $this->put('voice', null);
        $this->put('format', 'mp3');
        $this->put('settings', []);
    }

    /**
     * Sets the input text for the TTS prompt.
     *
     * @param string $string The input text.
     * @return $this Returns the current instance for method chaining.
     */
    public function setInput(string $string)
    {
        $this->put("input", $string);
        return $this;
    }

    /**
     * Sets the voice for the TTS prompt.
     *
     * @param string $string The voice identifier.
     * @return $this Returns the current instance for method chaining.
     */
    public function setVoice(string $string)
    {
        $this->put("voice", $string);
        return $this;
    }

    /**
     * Sets the output format for the TTS prompt.
     *
     * @param string $string The output format (e.g., "mp3").
     * @return $this Returns the current instance for method chaining.
     */
    public function setFormat(string $string)
    {
        $this->put("format", $string);
        return $this;
    }

    /**
     * Sets additional settings for the TTS prompt.
     *
     * @param array $keyValueArray An associative array of settings.
     * @return $this Returns the current instance for method chaining.
     */
    public function settings(array $keyValueArray)
    {
        $this->put("settings", $keyValueArray);
        return $this;
    }

    /**
     * Retrieves the input text for the TTS prompt.
     *
     * @return mixed The input text.
     */
    public function getInput()
    {
        return $this->get('input');
    }

    /**
     * Retrieves the voice setting for the TTS prompt.
     *
     * @return mixed The voice identifier.
     */
    public function getVoice()
    {
        return $this->get('voice');
    }

    /**
     * Retrieves the output format for the TTS prompt.
     *
     * @return mixed The output format.
     */
    public function getFormat()
    {
        return $this->get('format');
    }

    /**
     * Retrieves the additional settings for the TTS prompt.
     *
     * @return mixed The settings array.
     */
    public function getSettings()
    {
        return $this->get('settings');
    }

    /**
     * Encodes the TTS prompt parameters into a JSON string.
     *
     * Constructs a custom formatted string using the following pattern:
     * "{{VFS:voice,format,settings}}", where the settings are concatenated as key|value pairs.
     * This string is appended to the input text, and then the complete prompt is JSON-encoded.
     *
     * @return string The JSON-encoded representation of the prompt.
     */
    protected function encoder() : string
    {
        $input = $this->getInput();
        $voice = $this->getVoice();
        $format = $this->getFormat();
        $settings = $this->getSettings();
        $stringifySettings = '';

        foreach ($settings as $key => $setting) {
            $stringifySettings .= $stringifySettings 
                ? ",{$key}|{$setting}" 
                : "{$key}|{$setting}";
        }

        $vfsString = "{{VFS:{$voice},{$format},{$stringifySettings}}}";
        $encodedPromptString = $input . $vfsString;
        $encodedPrompt = ['prompt' => $encodedPromptString];

        return json_encode($encodedPrompt, JSON_PRETTY_PRINT);
    }

    /**
     * Decodes a JSON-encoded prompt string and extracts TTS parameters.
     *
     * Extracts the VFS block from the prompt using a regular expression, splits it to obtain
     * the voice, format, and settings values, and then updates the prompt using the corresponding
     * setter methods.
     *
     * @param string $prompt The JSON-encoded prompt string.
     * @return void
     */
    protected function decoder(string $prompt)
    {
        $promptData = json_decode($prompt);

        preg_match('/{{VFS:(.*?)}}/', $promptData->prompt, $entities);

        // Set default values.
        $i = $promptData->prompt;
        $v = '';
        $f = 'mp3';
        $s = [];

        if (count($entities) > 0) {
            $data = $entities[1];
            $VFS = explode(',', $data);
            $i = preg_replace('/{{VFS:.*?}}/', '', $promptData->prompt);
            $v = $VFS[0];
            $f = $VFS[1] ?? 'mp3';
            foreach ($VFS as $key => $value) {
                if ($key > 1) {
                    $key_value = explode('|', $value);
                    $s[$key_value[0]] = $key_value[1];
                }
            }
        }

        $this->setInput($i);
        $this->setVoice($v);
        $this->setFormat($f);
        $this->settings($s);
    }
}