<?php
namespace LaravelNeuro\Pipelines\ElevenLabs;

use Generator;
use Illuminate\Support\Str;
use LaravelNeuro\Prompts\IVFSprompt;
use LaravelNeuro\Drivers\WebRequest\GuzzleDriver;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Contracts\AiModel\Driver;

/**
 * Implements an ElevenLabs TTS pipeline for audio generation.
 * 
 * This pipeline uses an underlying driver (default: GuzzleDriver) to make HTTP requests to the ElevenLabs API,
 * setting the model, prompt, and voice parameters based on configuration and input IVFSprompt instances.
 * It handles header configuration, API endpoint setup, and request modification to include voice settings.
 * The output is stored as an audio file (default format: mp3) with a UUID as its filename.
 *
 * @package LaravelNeuro
 */
class AudioTTS implements Pipeline {

    /**
     * The model identifier used for the request.
     *
     * @var mixed
     */
    protected $model;

    /**
     * The AI model driver used for HTTP communication.
     * Expected to implement the Driver contract.
     *
     * @var GuzzleDriver
     */
    protected GuzzleDriver $driver;

    /**
     * The prompt text extracted from the provided IVFSprompt instance.
     *
     * @var mixed
     */
    protected $prompt;

    /**
     * The default voice to use, as configured in the package configuration.
     *
     * @var mixed
     */
    protected $voice;

    /**
     * The output file type (default: "mp3").
     *
     * @var string
     */
    protected $fileType = "mp3";

    /**
     * The ElevenLabs access token from configuration.
     *
     * @var mixed
     */
    protected $accessToken;
 
    /**
     * AudioTTS constructor.
     *
     * Injects a Driver instance (defaulting to GuzzleDriver) and sets up the pipeline using configuration values.
     * It retrieves the voice, model, API endpoint, and access token from the config, and sets the appropriate
     * headers required by the ElevenLabs API. If any required configuration is missing, an exception is thrown.
     *
     * @param Driver $driver An instance of a class implementing the Driver contract.
     * @throws \InvalidArgumentException if required configuration values are missing.
     */
    public function __construct(Driver $driver = new GuzzleDriver)
    {
        $this->driver = $driver;
        $this->prompt = [];
        
        $this->voice = config('laravelneuro.models.eleven-monolingual-v1.voice');
        $model = config('laravelneuro.models.eleven-monolingual-v1.model');
        $api = config('laravelneuro.models.eleven-monolingual-v1.api');
        $this->accessToken = config('laravelneuro.keychain.elevenlabs');

        $this->setModel($model);
        $this->driver->setApi($api);

        $this->driver->setHeaderEntry("xi-api-key", $this->accessToken);
        $this->driver->setHeaderEntry("Content-Type", "application/json");
        $this->driver->setHeaderEntry("Accept", "audio/mpeg");

        if(empty($this->model)) {
            throw new \InvalidArgumentException("No model name has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        }
        if(empty($this->driver->getApi())) {
            throw new \InvalidArgumentException("No API address has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        }
        if(empty($this->accessToken)) {
            throw new \InvalidArgumentException("No ElevenLabs access token has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        }
    }

    /**
     * Retrieves the class name of the default associated prompt.
     */
    public function promptClass() : string
    {
        return IVFSprompt::class;
    }

    /**
     * Retrieves the class name of the default associated driver.
     */
    public function driverClass() : string
    {
        return GuzzleDriver::class;
    }

    /**
     * Retrieves the current driver.
     *
     * @return Driver The current driver instance.
     */
    public function getDriver() : Driver
    {
        return $this->driver;
    }

    /**
     * Accesses the injected Driver instance.
     *
     * @return Driver the Driver instance stored in this instance of the class.
     */
    public function driver() : Driver
    {
        return $this->driver;
    }

    /**
     * Sets the model for the pipeline.
     *
     * Assigns the given model to the pipeline and updates the underlying driver's request payload.
     *
     * @param mixed $model The model identifier to be used.
     * @return self
     */
    public function setModel($model) : self
    {
        $this->model = $model;
        $this->driver->modifyRequest("model_id", $model);
        return $this;
    }

    /**
     * Retrieves the current model identifier.
     *
     * @return mixed The current model identifier.
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the prompt for the pipeline.
     *
     * Expects an IVFSprompt instance. The method sets default voice settings if none are provided,
     * extracts the input from the prompt, updates the driver's prompt (using a key "text"), and modifies
     * the API endpoint by replacing the {voice} placeholder with the prompt's voice value or the default voice.
     * It also converts the prompt's settings to a standard object and updates the driver's request with voice settings.
     *
     * @param IVFSprompt $prompt An instance of IVFSprompt.
     * @return self
     * @throws \InvalidArgumentException If the provided prompt is not an IVFSprompt.
     */
    public function setPrompt($prompt) : self
    {
        if($prompt instanceof IVFSprompt) {
            if(empty($prompt->getSettings())) {
                $prompt->settings([
                    "similarity_boost" => 0.5, 
                    "stability" => 0.5
                ]);
            }
            $this->prompt = $prompt->getInput();  
            $this->driver->setPrompt($this->prompt, "text");
            // Replace the {voice} placeholder with the appropriate voice value.
            $this->driver->setApi(
                str_replace("{voice}", ($prompt->getVoice() ?? $this->voice), $this->driver->getApi())
            );

            // Convert settings to a stdClass via json encode/decode for consistency.
            $voice_settings = json_decode(json_encode($prompt->getSettings()));
            if(isset($prompt->getSettings()["stability"]))
                $voice_settings->stability = $prompt->getSettings()["stability"];
            if(isset($prompt->getSettings()["similarity_boost"]))
                $voice_settings->similarity_boost = $prompt->getSettings()["similarity_boost"];
            if(isset($prompt->getSettings()["style"]))
                $voice_settings->style = $prompt->getSettings()["style"];
            if(isset($prompt->getSettings()["use_speaker_boost"]))
                $voice_settings->use_speaker_boost = $prompt->getSettings()["use_speaker_boost"];

            $this->driver->modifyRequest("voice_settings", $voice_settings);
        } else {
            throw new \InvalidArgumentException("Non-IVFS prompts are unlikely to work with ElevenLabs' voice endpoint. If you have your own Prompt model, you can apply it by extending this AudioTTS class and overriding the setPrompt method.");
        }
        
        return $this;
    }

    /**
     * Retrieves the current prompt input.
     *
     * @return mixed The current prompt.
     */
    public function getPrompt()
    {
        return $this->prompt;
    }

    /**
     * Executes the API request and returns the stored output.
     *
     * Internally calls the store() method to process and save the audio output.
     *
     * @param bool $json (Optional) If true, returns a JSON-encoded string of file metadata.
     * @return mixed The file metadata or JSON-encoded string, depending on the $json parameter.
     */
    public function output($json = true)
    {
        return $this->store($json);
    }

    /**
     * Retrieves the raw response from the driver.
     *
     * @return mixed The raw response body.
     */
    public function raw()
    {
        $body = $this->driver->output();
        return $body;
    }

    /**
     * Stores the audio output as a file.
     *
     * Generates a UUID-based filename with the defined file type (default "mp3"),
     * uses the driver's fileMake method to save the audio data, and returns the file metadata.
     *
     * @param bool $json (Optional) If true, returns JSON-encoded file metadata.
     * @return mixed The file metadata array or its JSON-encoded representation.
     */
    public function store($json = false)
    {
        $audio = $this->raw();
        $file = (string) Str::uuid() . '.' . $this->fileType;
        $fileMetaData = $this->driver->fileMake($file, $audio);
        if ($json) {
            return json_encode($fileMetaData);
        } else {
            return $fileMetaData;
        }
    }

    /**
     * Executes a streaming API request.
     *
     * This pipeline does not support stream mode. Calling this method will always
     * throw an exception.
     *
     * @return Generator
     * @throws \Exception Always throws an exception indicating that stream mode is not supported.
     */
    public function stream() : Generator
    {
        throw new \Exception("Stream mode is not supported for this pipeline.");
    }
}