<?php
namespace LaravelNeuro\Pipelines\Google;

use Generator;
use LaravelNeuro\Prompts\SUAprompt;
use LaravelNeuro\Drivers\WebRequest\GuzzleDriver;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Contracts\AiModel\Driver;

/**
 * Implements a Google multimodal pipeline for AI models that accept both text and file inputs.
 * 
 * This pipeline retrieves configuration values for the Google Gemini model and API, sets up
 * required headers and parameters (including an access token), and processes SUAprompt instances
 * to format the request payload appropriately.
 *
 * The pipeline supports output retrieval in multiple formats (text, JSON, array) and also provides
 * streaming methods that yield output chunks.
 *
 * @package LaravelNeuro
 */
class Multimodal implements Pipeline {

    /**
     * The model identifier used for the request.
     *
     * @var mixed
     */
    protected $model;

    /**
     * The driver instance for making API requests.
     *
     * @var GuzzleDriver
     */
    protected GuzzleDriver $driver;

    /**
     * The prompt data, processed from an SUAprompt instance.
     *
     * @var mixed
     */
    protected $prompt;

    /**
     * The Google Gemini API access token.
     *
     * @var mixed
     */
    protected $accessToken;

    /**
     * The base API endpoint.
     *
     * @var mixed
     */
    protected $baseApi;

    /**
     * Multimodal constructor.
     *
     * Retrieves configuration values for the Google Gemini API (model, endpoint, and access token),
     * initializes the driver (defaulting to GuzzleDriver), sets required header entries, and validates
     * that all required configuration values are present.
     *
     * @param Driver $driver An instance implementing the Driver contract.
     * @throws \InvalidArgumentException if any required configuration value is missing.
     */
    public function __construct(Driver $driver = new GuzzleDriver)
    {
        $this->driver = $driver;
        $this->prompt = [];

        $this->accessToken = config('laravelneuro.keychain.google');
        $this->baseApi = config('laravelneuro.models.gemini-pro-1-5.api');

        $this->setModel(config('laravelneuro.models.gemini-pro-1-5.model'));
        $this->driver->setHeaderEntry("Content-Type", "application/json");

        if (empty($this->model)) {
            throw new \InvalidArgumentException("No model name has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        }
        if (empty($this->driver->getApi())) {
            throw new \InvalidArgumentException("No API address has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        }
        if (empty($this->accessToken)) {
            throw new \InvalidArgumentException("No Google Gemini API access token has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        }
    }

    /**
     * Retrieves the class name of the default associated prompt.
     */
    public function promptClass() : string
    {
        return SUAprompt::class;
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
     * Sets the API endpoint for the driver.
     *
     * Builds the full API URL by appending the model identifier, generate endpoint (or stream endpoint),
     * and the access token to the provided base address. Updates the driver's API endpoint and the base API.
     *
     * @param string $address The base API address.
     * @param bool $stream Optional. If true, sets up the endpoint for streaming.
     * @return self
     * @throws \InvalidArgumentException If the access token is missing.
     */
    public function setApi($address, $stream = false) : self
    {
        $model = !empty($this->getModel()) ? $this->getModel() : '{model}';
        if (!empty($this->accessToken)) {
            $key = $this->accessToken;
        } else {
            throw new \InvalidArgumentException("No Google Gemini API access token has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        }
        $generate = !$stream ? ':generateContent?key=' : ':streamGenerateContent?alt=sse&key=';
        $this->driver->setApi($address . '/' . $model . $generate . $key);
        $this->baseApi = $address;
        return $this;
    }

    /**
     * Sets the model for the pipeline.
     *
     * Stores the model identifier and updates the API endpoint accordingly.
     *
     * @param mixed $model The model identifier.
     * @return self
     */
    public function setModel($model) : self
    {
        $this->model = $model;
        $this->setApi($this->baseApi);
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
     * Expects a SUAprompt instance. Processes each element in the prompt:
     * - If the element contains a file pattern and is not of type "role", it converts it into an inline data structure.
     * - Otherwise, wraps the text in an associative array.
     * The method also extracts a system instruction from a "role" element, if present,
     * and updates the driver's prompt and system_instruction request parameter.
     *
     * @param SUAprompt $prompt A SUAprompt instance.
     * @return self
     * @throws \InvalidArgumentException if the prompt is not a SUAprompt instance.
     */
    public function setPrompt($prompt) : self
    {
        if ($prompt instanceof SUAprompt) {
            $this->prompt = [];
            foreach ($prompt as $element) {
                if (preg_match('/\[file:([a-zA-Z0-9+\/.-]+)\|([a-zA-Z0-9+\/=]+)\]/', $element->block, $filepart) && $element->type != "role") {
                    // Process file inline data.
                    [$drop, $mime, $b64] = $filepart;
                    $element->block = [
                        "inlineData" => [
                            "mimeType" => $mime,
                            "data" => $b64
                        ]
                    ];
                    [$drop, $mime, $b64] = [null, null, null];
                } else {
                    $element->block = ["text" => $element->block];
                }
                switch ($element->type) {
                    case "role":
                        $role = $element->block;
                        break;
                    case "agent":
                        $this->prompt[] = [
                            "role" => "model",
                            "parts" => $element->block
                        ];
                        break;
                    case "user":
                        $this->prompt[] = [
                            "role" => "user",
                            "parts" => $element->block
                        ];
                    default:
                        break;
                }
            }
            if (isset($role)) {
                $this->driver->setPrompt($this->prompt, "contents");
                $this->driver->modifyRequest("system_instruction", [
                    "parts" => $role
                ]);
            } else {
                $this->driver->setPrompt($this->prompt, "contents");
            }
        } else {
            throw new \InvalidArgumentException("For this pipeline, the parameter passed to setPrompt should be a SUAprompt Object");
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
     * Executes the API request and returns the generated text output.
     *
     * This method is an alias for text().
     *
     * @return string The generated text output.
     */
    public function output() : string
    {
        return $this->text();
    }

    /**
     * Retrieves the text output from the API response.
     *
     * Decodes the response and returns the text content from the first candidate.
     *
     * @return string The text output.
     */
    public function text() : string
    {
        $body = $this->driver->output();
        return json_decode((string)$body)->candidates[0]->content->parts[0]->text;
    }

    /**
     * Retrieves the API response as a JSON-formatted string.
     *
     * @return string The JSON-encoded API response.
     */
    public function json() : string
    {
        $body = $this->driver->output();
        return json_encode(json_decode($body), JSON_PRETTY_PRINT);
    }

    /**
     * Retrieves the API response as an associative array.
     *
     * @return array The decoded API response.
     */
    public function array() : array
    {
        $body = $this->driver->output();
        return json_decode($body, true);
    }

    /**
     * Executes a streaming API request and yields JSON-encoded output chunks.
     *
     * Sets the API to streaming mode, processes each output chunk, and yields the JSON-encoded chunk.
     *
     * @return Generator Yields JSON-encoded data chunks.
     */
    public function stream() : Generator
    {
        $this->setApi($this->baseApi, true);
        $body = $this->driver->stream();
        foreach ($body as $output) {
            $output = json_decode($output);
            yield json_encode($output, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Executes a streaming API request and yields text output chunks.
     *
     * Sets the API to streaming mode, processes each output chunk, and yields the text content
     * if available in the candidate's content parts.
     *
     * @return Generator Yields text output chunks.
     */
    public function streamText() : Generator
    {
        $this->setApi($this->baseApi, true);
        $body = $this->driver->stream();
        foreach ($body as $output) {
            $output = (object) json_decode($output);
            if (property_exists($output->candidates[0]->content->parts[0], "text")) {
                yield $output->candidates[0]->content->parts[0]->text;
            }
        }
    }

    /**
     * Executes a streaming API request and yields output as decoded arrays.
     *
     * Sets the API to streaming mode, processes each output chunk, and yields the decoded output.
     *
     * @return Generator Yields decoded output as an array.
     */
    public function streamArray() : Generator
    {
        $this->setApi($this->baseApi, true);
        $body = $this->driver->stream();
        foreach ($body as $output) {
            $output = json_decode($output);
            yield $output;
        }
    }
}