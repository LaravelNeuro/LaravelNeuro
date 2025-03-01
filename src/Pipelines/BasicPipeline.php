<?php
namespace LaravelNeuro\Pipelines;

use Generator;
use LaravelNeuro\Prompts\SUAprompt;
use LaravelNeuro\Prompts\BasicPrompt;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Contracts\AiModel\Driver;
use LaravelNeuro\Drivers\WebRequest\GuzzleDriver;

/**
 * Provides a builder-pattern interface for constructing AI API requests by delegating functionality to an injected Driver. 
 * 
 * The BasicPipeline allows you to set the model and prompt which are then passed 
 * through to the driver. It supports both standard and streaming outputs.
 *
 * @package LaravelNeuro
 */
class BasicPipeline implements Pipeline {

    /**
     * The AI model driver.
     *
     * Should implement the Driver contract.
     *
     * @var Driver
     */
    protected Driver $driver;

    /**
     * The model identifier used for the request.
     *
     * @var mixed
     */
    protected $model;

    /**
     * The prompt text to be used for the request.
     *
     * @var mixed
     */
    protected $prompt;

    /**
     * The system message or instruction, if applicable.
     *
     * @var mixed
     */
    protected $system;

    /**
     * BasicPipeline constructor.
     *
     * Optionally accepts a Driver instance. If none is provided, a default GuzzleDriver is used.
     *
     * @param Driver $driver An instance of a class implementing the Driver contract.
     */
    public function __construct(Driver $driver = new GuzzleDriver)
    {
        $this->driver = $driver;
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
        $this->driver->setModel($model);
        return $this;
    }

    /**
     * Sets the prompt for the pipeline.
     *
     * Accepts either a string or an instance of SUAprompt. If a SUAprompt is provided,
     * the method iterates over its elements to build the complete prompt and system message.
     *
     * @param string|BasicPrompt $prompt The prompt text or SUAprompt instance.
     * @return self
     * @throws \InvalidArgumentException If the provided prompt is neither a string nor an instance of SUAprompt.
     */
    public function setPrompt(string|BasicPrompt $prompt) : self
    {
        if (is_string($prompt)) {
            $this->prompt = $prompt;
        } elseif ($prompt instanceof SUAprompt) {
            $this->prompt = '';
            foreach ($prompt as $element) {
                switch ($element->type) {
                    case "role":
                        $this->driver->setSystemPrompt($element->block);
                        break;
                    default:
                        $this->prompt .= $element->block . "\n";
                        break;
                }
            }
        } else {
            throw new \InvalidArgumentException("For this pipeline, the parameter passed to setPrompt should be a string or an instance of SUAprompt.");
        }
        $this->driver->setPrompt($this->prompt);
        return $this;
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
     * Retrieves the current model identifier.
     *
     * @return mixed The current model identifier.
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Retrieves the current prompt text.
     *
     * @return string The current prompt.
     */
    public function getPrompt()
    {
        return $this->prompt;
    }

    /**
     * Executes the API request and returns the response body.
     *
     * Delegates to the driver's output() method.
     *
     * @return mixed The response body.
     */
    public function output()
    {
        return $this->driver->output();
    }

    /**
     * Executes a streaming API request.
     *
     * Enables streaming mode and returns a generator that yields JSON-encoded
     * data chunks from the API response.
     *
     * @return Generator Yields JSON-encoded data chunks.
     */
    public function stream() : Generator
    {
        return $this->driver->stream();
    }
}