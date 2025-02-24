<?php
namespace LaravelNeuro\Pipelines;

use Generator;
use LaravelNeuro\Prompts\SUAPrompt;
use LaravelNeuro\Prompts\BasicPrompt;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Contracts\AiModel\Driver;
use LaravelNeuro\Drivers\WebRequests\GuzzleDriver;

/**
 * Class BasicPipeline
 *
 * Injects an Ai Model Driver to provide a builder-pattern interface for constructing API requests.
 * The Pipeline class adds structure by incorporating a model and a prompt to the request.
 *
 * @package LaravelNeuro
 */
class BasicPipeline implements Pipeline {

    /**
     * The pipeline's ai model driver.
     * Should implement the Driver Contract. 
     *
     * @var Driver
     */
    protected Driver $driver;

    /**
     * The model identifier used in the Driver request.
     *
     * @var mixed
     */
    protected $model;

    /**
     * The prompt text to be used in the Driver request.
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

    public function __construct(Driver $driver=new GuzzleDriver) {
        $this->driver = $driver;
    }
    
    /**
     * Sets the model for the pipeline.
     *
     * This method assigns the given model to the pipeline and updates the request payload accordingly.
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
     * This method accepts either a string or an instance of SUAPrompt. If a SUAPrompt instance is provided,
     * it iterates over the prompt elements to build the complete prompt and system message.
     *
     * @param string|SUAPrompt $prompt The prompt text or SUAPrompt instance.
     * @return self
     * @throws \InvalidArgumentException If the provided prompt is neither a string nor an instance of SUAPrompt.
     */
    public function setPrompt(string|BasicPrompt $prompt) : self
    {
        if(is_string($prompt))
        {
            $this->prompt = $prompt;
        }
        elseif($prompt instanceof SUAprompt)
        {
            $this->prompt = '';

            foreach($prompt as $element)
            {
                switch($element->type)
                {
                    case "role":
                        $this->driver->setSystemPrompt($element->block);
                        break;
                    default:
                        $this->prompt .= $element->block."\n";
                        break;
                }
                
            }

        }
        else
        {
                throw new \InvalidArgumentException("For this pipeline, the paramater passed to setPrompt should be a string or an instance of SUAprompt.");
        }
        $this->driver->setPrompt($this->prompt);

        return $this;
    }

    public function getDriver() : Driver
    {
        return $this->driver;
    }

    /**
     * Retrieves the current model.
     *
     * @return mixed The current model identifier.
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Retrieves the current prompt.
     *
     * @return string The current prompt text.
     */
    public function getPrompt()
    {
        return $this->prompt;
    }

    public function output()
    {
        return $this->driver->output();
    }

    /**
     * Executes a streaming Driver request.
     *
     * Enables streaming mode and returns a generator that yields JSON-encoded
     * data chunks from the API response. Useful for handling responses that
     * include large or streaming payloads.
     *
     * @return Generator Yields JSON-encoded data chunks.
     */
    public function stream() : Generator
    {
        return $this->driver->stream();
    }
}