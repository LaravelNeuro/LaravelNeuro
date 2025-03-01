<?php
namespace LaravelNeuro\Pipelines\OpenAI;

use Generator;
use LaravelNeuro\Prompts\SUAprompt;
use LaravelNeuro\Contracts\AiModel\Driver;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Drivers\WebRequest\GuzzleDriver;

/**
 * Implements an OpenAI ChatCompletion pipeline using the GPT-3.5-Turbo model as its default.
 * 
 * This pipeline uses an underlying driver (default: GuzzleDriver) to communicate with the OpenAI API.
 * It expects a SUAprompt instance to set the conversation messages, including system instructions,
 * user inputs, and assistant responses. The output methods provide access to the response as text,
 * JSON, or an array, and streaming methods are also supported.
 *
 * @package LaravelNeuro
 */
class ChatCompletion implements Pipeline {

    /**
     * The model identifier used for the API request.
     *
     * @var mixed
     */
    protected $model;

    /**
     * The driver instance used for HTTP communication.
     *
     * @var GuzzleDriver
     */
    protected GuzzleDriver $driver;

    /**
     * The prompt data, structured as an array of messages.
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
     * ChatCompletion constructor.
     *
     * Retrieves configuration values for the GPT-3.5-Turbo model and API endpoint,
     * initializes the driver (defaulting to GuzzleDriver), sets required HTTP headers,
     * and validates that all required configuration values are present.
     *
     * @param Driver $driver An instance implementing the Driver contract.
     * @throws \InvalidArgumentException if any required configuration value is missing.
     */
    public function __construct(Driver $driver = new GuzzleDriver)
    {
        $this->driver = $driver;
        $this->prompt = [];
        
        $this->setModel(config('laravelneuro.models.gpt-3-5-turbo.model'));
        $this->driver->setApi(config('laravelneuro.models.gpt-3-5-turbo.api'));
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
     * Updates both the pipeline and the underlying driver's model.
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
     * Expects a SUAprompt instance. Iterates through the prompt elements to build a structured
     * messages array where:
     * - "role" elements are transformed into system messages.
     * - "agent" elements become assistant messages.
     * - "user" elements become user messages.
     * The resulting messages array is then passed to the driver using the "messages" key.
     *
     * @param SUAprompt $prompt A SUAprompt instance.
     * @return self
     * @throws \InvalidArgumentException if the provided prompt is not a SUAprompt.
     */
    public function setPrompt($prompt) : self
    {
        if ($prompt instanceof SUAprompt) {
            $this->prompt = [];
            foreach ($prompt as $element) {
                switch ($element->type) {
                    case "role":
                        $role = [["role" => "system", "content" => $element->block]];
                        break;
                    case "agent":
                        $this->prompt[] = ["role" => "assistant", "content" => $element->block];
                        break;
                    case "user":
                        $this->prompt[] = ["role" => "user", "content" => $element->block];
                        break;
                    default:
                        break;
                }
            }
            // Merge system instruction with the other messages if set.
            if (isset($role)) {
                $this->driver->setPrompt(array_merge($role, $this->prompt), "messages");
            } else {
                $this->driver->setPrompt($this->prompt, "messages");
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
    public function output()
    {
        return $this->text();
    }

    /**
     * Retrieves the text output from the API response.
     *
     * Extracts and returns the content from the first choice in the response.
     *
     * @return string The text content from the API response.
     */
    public function text()
    {
        $body = $this->driver->output();
        return json_decode((string)$body)->choices[0]->message->content;
    }

    /**
     * Retrieves the API response as a JSON-formatted string.
     *
     * @return string The JSON-encoded API response.
     */
    public function json()
    {
        $body = $this->driver->output();
        return json_encode(json_decode($body), JSON_PRETTY_PRINT);
    }

    /**
     * Retrieves the API response as an associative array.
     *
     * @return array The decoded API response.
     */
    public function array()
    {
        $body = $this->driver->output();
        return json_decode($body, true);
    }

    /**
     * Executes a streaming API request and yields output chunks.
     *
     * Modifies the request to enable streaming mode and yields the raw stream output.
     *
     * @return Generator Yields the streaming output.
     */
    public function stream() : Generator
    {
        $this->driver->modifyRequest("stream", true);
        foreach($this->driver->stream() as $output)
        {
            yield $output;
        }
    }

    /**
     * Executes a streaming API request and yields text output chunks.
     *
     * Iterates over the streaming output and yields the text content from delta messages.
     *
     * @return Generator Yields text output chunks.
     */
    public function streamText() : Generator
    {
        foreach ($this->stream() as $output) {
            $output = (object) json_decode($output);
            if (property_exists($output->choices[0]->delta, "content")) {
                yield $output->choices[0]->delta->content;
            }
        }
    }

    /**
     * Executes a streaming API request and yields JSON-encoded output chunks.
     *
     * @return Generator Yields JSON-formatted output chunks.
     */
    public function streamJson() : Generator
    {
        foreach ($this->stream() as $output) {
            $output = json_decode($output);
            yield json_encode($output, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Executes a streaming API request and yields the output as decoded arrays.
     *
     * @return Generator Yields decoded output as an array.
     */
    public function streamArray() : Generator
    {
        foreach ($this->stream() as $output) {
            $output = json_decode($output);
            yield $output;
        }
    }
}