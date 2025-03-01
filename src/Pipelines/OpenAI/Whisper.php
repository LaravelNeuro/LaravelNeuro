<?php
namespace LaravelNeuro\Pipelines\OpenAI;

use Generator;
use GuzzleHttp\Psr7;
use LaravelNeuro\Prompts\FSprompt;
use LaravelNeuro\Enums\RequestType;
use LaravelNeuro\Contracts\AiModel\Driver;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Drivers\WebRequest\GuzzleDriver;

/**
 * Implements the OpenAI Whisper pipeline for audio transcription.
 * 
 * This pipeline uses an underlying driver (default: GuzzleDriver) to communicate with the OpenAI Whisper API.
 * It expects a prompt of type FSprompt containing file input, configures the driver to use multipart form data,
 * and returns the transcribed text from the API response.
 *
 * @package LaravelNeuro
 */
class Whisper implements Pipeline {

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
     * The prompt data.
     *
     * @var mixed
     */
    protected $prompt;

    /**
     * The OpenAI access token retrieved from configuration.
     *
     * @var mixed
     */
    protected $accessToken;

    /**
     * Whisper constructor.
     *
     * Retrieves configuration values for the Whisper model and API endpoint,
     * initializes the underlying driver (defaulting to GuzzleDriver), sets the required HTTP header
     * for authorization, and validates that all required configuration values are provided.
     *
     * @param Driver $driver An instance implementing the Driver contract.
     * @throws \InvalidArgumentException if any required configuration value is missing.
     */
    public function __construct(Driver $driver = new GuzzleDriver)
    {
        $this->driver = $driver;
        $this->prompt = [];
        $this->setModel(config('laravelneuro.models.whisper-1.model'));
        $this->driver->setApi(config('laravelneuro.models.whisper-1.api'));
        $this->accessToken = config('laravelneuro.keychain.openai');

        $this->driver->setHeaderEntry("Authorization", "Bearer " . $this->accessToken);

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
     * Updates the pipeline's model property and informs the driver of the model.
     *
     * @param mixed $model The model identifier.
     * @return self
     */
    public function setModel($model) : self
    {
        $this->model = $model;
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
     * Expects an instance of FSprompt, which provides the file to be transcribed.
     * Configures the driver to use multipart form data by setting the request type to MULTIPART,
     * and updates the driver's request payload with the file parameter.
     *
     * @param FSprompt $prompt A FSprompt instance containing file input.
     * @return self
     * @throws \InvalidArgumentException if the provided prompt is not an instance of FSprompt.
     */
    public function setPrompt($prompt) : self
    {
        if ($prompt instanceof FSprompt) {
            $this->driver->setRequestType(RequestType::MULTIPART);
            $this->driver->modifyRequest([
                                            [
                                            'name'     => 'file',
                                            'contents' => Psr7\Utils::tryFopen($prompt->getFile(), "r")
                                            ],
                                            [
                                                'name'     => 'model',
                                                'contents' => $this->getModel()
                                            ]
                                        ]);
        } else {
            throw new \InvalidArgumentException("Non-IVFS prompts are unlikely to work with OpenAI's voice endpoint. If you have your own Prompt model, you can apply it by extending this VoiceTTS class and overriding the setPrompt method.");
        }
        return $this;
    }

    /**
     * Retrieves the current prompt.
     *
     * @return mixed The prompt data.
     */
    public function getPrompt()
    {
        return $this->prompt;
    }

    /**
     * Executes the API request and returns the transcribed text.
     *
     * Parses the API response and returns the "text" field.
     *
     * @return mixed The transcribed text.
     */
    public function output()
    {
        $output = $this->driver->output();
        return json_decode($output)->text;
    }

    /**
     * Executes a streaming API request.
     *
     * This pipeline does not support streaming mode.
     *
     * @return Generator
     * @throws \Exception Always throws an exception indicating that stream mode is not supported.
     */
    public function stream() : Generator
    {
        throw new \Exception("Stream mode is not supported for this pipeline.");
    }
}