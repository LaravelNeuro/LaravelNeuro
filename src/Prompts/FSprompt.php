<?php
namespace LaravelNeuro\Prompts;

use LaravelNeuro\Prompts\BasicPrompt;

/**
 * FSprompt stands for "File, Settings" and is designed for multimodal pipelines that require file inputs, such as OpenAI's Whisper model. 
 * 
 * FSprompt extends BasicPrompt and provides methods to set and retrieve the file
 * and associated settings. The encoder method constructs a custom formatted string (FS block) that embeds
 * the file and settings information into a JSON-encoded prompt, while the decoder extracts these parameters
 * from such a prompt.
 *
 * @package LaravelNeuro
 */
class FSprompt extends BasicPrompt {

    /**
     * Sets the file parameter for the prompt.
     *
     * @param string $string The file identifier or path.
     * @return $this Returns the current instance for method chaining.
     */
    public function setFile(string $string)
    {
        $this->put('file', $string);
        return $this;
    }

    /**
     * Sets additional settings for the prompt.
     *
     * @param array $keyValueArray An associative array of settings.
     * @return $this Returns the current instance for method chaining.
     */
    public function settings(array $keyValueArray)
    {
        $this->put('settings', $keyValueArray);
        return $this;
    }

    /**
     * Retrieves the file parameter from the prompt.
     *
     * @return mixed The file value, or null if not set.
     */
    public function getFile()
    {
        return $this->get('file');
    }

    /**
     * Retrieves the additional settings from the prompt.
     *
     * @return mixed The settings array, or null if not set.
     */
    public function getSettings()
    {
        return $this->get('settings');
    }

    /**
     * Encodes the file and settings parameters into a JSON-encoded prompt string.
     *
     * This method constructs an FS block with the following format:
     * "{{FS:file,settingKey1|settingValue1,settingKey2|settingValue2,...}}"
     * which is then stored under the "prompt" key and JSON-encoded.
     *
     * @return string The JSON-encoded prompt containing the FS block.
     */
    protected function encoder() : string
    {
        $file = $this->getFile();
        $settings = $this->getSettings();
        $stringifySettings = '';

        foreach ($settings as $key => $setting) {
            $stringifySettings .= $stringifySettings 
                ? ",{$key}|{$setting}" 
                : "{$key}|{$setting}";
        }

        $fsString = "{{FS:{$file},{$stringifySettings}}}";
        $encodedPromptString = $fsString;
        $encodedPrompt = ['prompt' => $encodedPromptString];

        return json_encode($encodedPrompt, JSON_PRETTY_PRINT);
    }

    /**
     * Decodes a JSON-encoded prompt string and extracts file and settings parameters.
     *
     * This method looks for an FS block in the prompt (using a regular expression). If found,
     * it splits the block into its components, extracting the file identifier and settings values.
     * If no FS block is present, the entire prompt is treated as the file value.
     *
     * @param string $prompt The JSON-encoded prompt string.
     * @return void
     */
    protected function decoder(string $prompt)
    {
        $promptData = json_decode($prompt);
        preg_match('/{{FS:(.*?)}}/', $promptData->prompt, $entities);

        $f = '';
        $s = [];

        if (count($entities) > 0) {
            $data = $entities[1];
            $FS = explode(',', $data);
            $f = $FS[0] ?? '';
            foreach ($FS as $key => $value) {
                if ($key > 0) {
                    $key_value = explode('|', $value);
                    $s[$key_value[0]] = $key_value[1];
                }
            }
        } else {
            $f = $promptData->prompt;
        }

        $this->setFile($f);
        $this->settings($s);
    }
}