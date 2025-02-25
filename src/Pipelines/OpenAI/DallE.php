<?php
namespace LaravelNeuro\Pipelines\OpenAI;

use Generator;
use Illuminate\Support\Str;
use LaravelNeuro\Contracts\AiModel\Driver;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Drivers\WebRequest\GuzzleDriver;
use LaravelNeuro\Prompts\PNSQFprompt;

/**
 * Class DallE
 *
 * Implements the Dall-E image generation pipeline using OpenAI's API.
 * This pipeline requires a prompt of type PNSQFprompt to specify image generation
 * parameters such as prompt text, number of images, size, quality, and response format.
 * It configures the underlying driver (default: GuzzleDriver) with the appropriate API
 * endpoint, model, and headers including the OpenAI access token.
 * The output methods allow retrieval of images in various formats (base64, raw binary, or stored files).
 *
 * @package LaravelNeuro
 */
class DallE implements Pipeline {

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
     * The prompt data, extracted from a PNSQFprompt instance.
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
     * The file type for the generated image (default: "png").
     *
     * @var string
     */
    protected $fileType = "png";

    /**
     * DallE constructor.
     *
     * Retrieves configuration values for the Dall-E model and API endpoint,
     * initializes the underlying driver (defaulting to GuzzleDriver), and sets
     * the required HTTP headers including the OpenAI access token.
     * Throws an exception if any required configuration value is missing.
     *
     * @param Driver $driver An instance implementing the Driver contract.
     * @throws \InvalidArgumentException if required configuration values are missing.
     */
    public function __construct(Driver $driver = new GuzzleDriver)
    {
        $this->driver = $driver;
        $this->prompt = [];
        $this->setModel(config('laravelneuro.models.dall-e-2.model'));
        $this->driver->setApi(config('laravelneuro.models.dall-e-2.api'));
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
     * Sets the model for the pipeline.
     *
     * Updates both the pipeline property and the driver's model.
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
     * Expects a PNSQFprompt instance. Extracts the prompt text and image generation
     * parameters (number, size, quality, and response_format) from the prompt, then
     * updates the driver's request accordingly.
     *
     * @param PNSQFprompt $prompt A PNSQFprompt instance.
     * @return self
     * @throws \Exception If the provided prompt is not an instance of PNSQFprompt.
     */
    public function setPrompt($prompt) : self
    {
        if ($prompt instanceof PNSQFprompt) {
            $this->prompt = $prompt->getPrompt();
            $this->driver->setPrompt($this->prompt, "prompt");
            $this->driver->modifyRequest("n", $prompt->getNumber());
            $this->driver->modifyRequest("size", $prompt->getSize());
            $this->driver->modifyRequest("quality", $prompt->getQuality());
            $this->driver->modifyRequest("response_format", $prompt->getFormat());
        } else {
            throw new \Exception("The DallE Pipeline requires a PNSQFprompt class prompt.");
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
     * Sets the file type for the output image.
     *
     * @param string $type The file type (e.g., "png").
     * @return self
     */
    public function setFileType(string $type)
    {
        $this->fileType = $type;
        return $this;
    }

    /**
     * Executes the API request and returns the output.
     *
     * This method is an alias for store().
     *
     * @param bool $json Optional. If true, returns JSON-encoded file metadata.
     * @return mixed The file metadata or JSON-encoded representation.
     */
    public function output($json = true)
    {
        return $this->store($json);
    }

    /**
     * Retrieves the image data as base64-encoded strings.
     *
     * Iterates over the response data and collects any base64-encoded JSON values.
     *
     * @return array An array of base64-encoded image strings.
     */
    public function b64()
    {
        $body = $this->driver->output();
        $images = json_decode($body)->data;
        $images = [];
        foreach ($images as $data) {
            if (property_exists($data, 'b64_json')) {
                $images[] = $data->b64_json;
            }
        }
        return $images;
    }

    /**
     * Retrieves the image data from the API response.
     *
     * Processes the response data to decode base64 image strings or return URLs.
     *
     * @return array An array of image data (binary data or URLs).
     */
    public function raw()
    {
        $body = $this->driver->output();
        $imagedata = json_decode($body)->data;
        $images = [];
        foreach ($imagedata as $data) {
            if (property_exists($data, 'b64_json')) {
                $images[] = base64_decode($data->b64_json);
            } elseif (property_exists($data, 'url')) {
                $images[] = $data->url;
            }
        }
        return $images;
    }

    /**
     * Stores the generated image(s) as file(s) and returns file metadata.
     *
     * Iterates over the image data, saving each image using the driver's fileMake method,
     * and returns an array of file metadata. If an image is provided as a URL, it is returned as-is.
     *
     * @param bool $json Optional. If true, returns a JSON-encoded string of file metadata.
     * @return mixed The file metadata array or its JSON-encoded representation.
     */
    public function store($json = false)
    {
        $images = $this->raw();
        $fileMetaData = [];
        foreach ($images as $image) {
            if (!filter_var($image, FILTER_VALIDATE_URL)) {
                $file = (string) Str::uuid() . '.' . $this->fileType;
                $fileMetaData[] = $this->driver->fileMake($file, $image);
            } else {
                $fileMetaData[] = ["url" => $image];
            }
        }
        if ($json) {
            return json_encode($fileMetaData);
        } else {
            return $fileMetaData;
        }
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