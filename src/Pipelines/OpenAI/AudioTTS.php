<?php
namespace LaravelNeuro\Pipelines\OpenAI;

use Generator;
use Illuminate\Support\Str;
use LaravelNeuro\Prompts\IVFSprompt;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Contracts\AiModel\Driver;
use LaravelNeuro\Drivers\WebRequest\GuzzleDriver;

/**
 * Implements an OpenAI TTS pipeline for audio generation.
 * This pipeline uses an underlying driver (default: GuzzleDriver) to communicate
 * with the OpenAI API for text-to-speech generation. It sets the model and API endpoint
 * based on configuration, applies required HTTP headers including the access token, and
 * expects an IVFSprompt instance to configure the request. The resulting audio output is
 * stored as a file (with a UUID-based filename).
 * 
 * You can find available voices at: https://platform.openai.com/docs/guides/text-to-speech
 * They include: alloy, ash, coral, echo, fable, onyx, nova, sage, shimmer
 *
 * @package LaravelNeuro
 */
class AudioTTS implements Pipeline {

    /**
     * The model identifier used for the API request.
     *
     * @var mixed
     */
    protected $model;

    /**
     * The driver instance used for HTTP requests.
     *
     * @var GuzzleDriver
     */
    protected GuzzleDriver $driver;

    /**
     * The prompt input, extracted from an IVFSprompt instance.
     *
     * @var mixed
     */
    protected $prompt;

    /**
     * The output file type (e.g., "mp3").
     *
     * @var string
     */
    protected $fileType;

    /**
     * The OpenAI access token retrieved from configuration.
     *
     * @var mixed
     */
    protected $accessToken;

    /**
     * AudioTTS constructor.
     *
     * Retrieves configuration values for the API endpoint, model, and access token,
     * sets up the underlying driver, and configures necessary HTTP headers.
     * Throws an exception if any required configuration value is missing.
     *
     * @param Driver $driver An instance implementing the Driver contract.
     * @throws \InvalidArgumentException if required configuration values are missing.
     */
    public function __construct(Driver $driver = new GuzzleDriver)
    {
        $this->driver = $driver;

        $api = config('laravelneuro.models.tts-1.api');
        $model = config('laravelneuro.models.tts-1.model');
        
        $this->prompt = [];
        $this->setModel($model);
        $this->driver->setApi($api);
        $this->accessToken = config('laravelneuro.keychain.openai');

        $this->driver->setHeaderEntry("Authorization", "Bearer " . $this->accessToken);
        $this->driver->setHeaderEntry("Content-Type", "application/json");

        if (empty($this->model)) {
            throw new \InvalidArgumentException("No model name has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        }
        if (empty($this->driver->getApi())) {
            throw new \InvalidArgumentException("No API address has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        }
        if (empty($this->accessToken)) {
            throw new \InvalidArgumentException("No OpenAI access token has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        }
    }

    /**
     * Retrieves the current driver.
     *
     * @return Driver The driver instance.
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
     * Updates both the pipeline property and the driver's request payload.
     *
     * @param mixed $model The model identifier.
     * @return self
     */
    public function setModel($model) : self
    {
        $this->model = $model;
        $this->driver->setModel($model);
        return $this;
    }

    /**
     * Retrieves the current model identifier.
     *
     * @return mixed The model identifier.
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the prompt for the pipeline.
     *
     * Expects an IVFSprompt instance. Extracts input, voice, and format from the prompt,
     * configures the driver's prompt, and updates the request with the appropriate voice
     * and response_format parameters. Also stores the file type based on the prompt format.
     *
     * @param IVFSprompt $prompt An IVFSprompt instance.
     * @return self
     * @throws \InvalidArgumentException If the provided prompt is not an IVFSprompt.
     */
    public function setPrompt($prompt) : self
    {
        if ($prompt instanceof IVFSprompt) {
            $this->prompt = $prompt->getInput();
            $this->driver->setPrompt($this->prompt, "input");
            $this->driver->modifyRequest("voice", $prompt->getVoice() ?? config('laravelneuro.models.tts-1.voice', 'onyx'));
            $this->driver->modifyRequest("response_format", $prompt->getFormat());

            $this->fileType = $prompt->getFormat();
            if (isset($prompt->getSettings()["speed"])) {
                $this->driver->modifyRequest("speed", $prompt->getQuality());
            }
        } else {
            throw new \InvalidArgumentException("Non-IVFS prompts are unlikely to work with OpenAI's voice endpoint. If you have your own Prompt model, you can apply it by extending this VoiceTTS class and overriding the setPrompt method.");
        }
    
        return $this;
    }

    /**
     * Retrieves the current prompt input.
     *
     * @return mixed The prompt input.
     */
    public function getPrompt()
    {
        return $this->prompt;
    }

    /**
     * Executes the API request and returns the stored output.
     *
     * This method is an alias for the store() method.
     *
     * @param bool $json Optional. If true, returns JSON-encoded file metadata.
     * @return mixed The file metadata or its JSON-encoded representation.
     */
    public function output($json = true)
    {
        return $this->store($json);
    }

    /**
     * Executes the API request, stores the audio output as a file, and returns file metadata.
     *
     * Generates a UUID-based filename with the defined file type, saves the audio data
     * using the driver's fileMake method, and returns metadata about the stored file.
     *
     * @param bool $json Optional. If true, returns JSON-encoded file metadata.
     * @return mixed The file metadata array or its JSON-encoded representation.
     */
    public function store($json = false)
    {
        $audio = $this->driver->output();
        $file = (string) Str::uuid() . '.' . $this->fileType;
        $fileMetaData = $this->driver->fileMake($file, $audio);
        if ($json) {
            return json_encode($fileMetaData);
        } else {
            return $fileMetaData;
        }
    }

    /**
     * Retrieves the raw API response.
     *
     * @return mixed The raw audio data.
     */
    public function raw()
    {
        $audio = $this->driver->output();
        return $audio;
    }

    /**
     * Executes a streaming API request.
     *
     * This pipeline does not support streaming mode.
     *
     * @return Generator
     * @throws \Exception Always throws an exception indicating streaming is not supported.
     */
    public function stream() : Generator
    {
        throw new \Exception("Stream mode is not supported for this pipeline.");
    }
}