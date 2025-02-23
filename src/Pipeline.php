<?php
namespace LaravelNeuro;

use LaravelNeuro\ApiAdapter;
use LaravelNeuro\Prompts\SUAPrompt;

/**
 * Class Pipeline
 *
 * Extends the ApiAdapter to provide a builder-pattern interface for constructing API requests.
 * The Pipeline class adds structure by incorporating a model and a prompt to the request.
 *
 * @package LaravelNeuro
 */
class Pipeline extends ApiAdapter {

    /**
     * The model identifier used in the API request.
     *
     * @var mixed
     */
    protected $model;

    /**
     * The prompt text to be used in the API request.
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
     * The complete request payload.
     *
     * @var array
     */
    public $request;
    
    /**
     * Sets the model for the pipeline.
     *
     * This method assigns the given model to the pipeline and updates the request payload accordingly.
     *
     * @param mixed $model The model identifier to be used.
     * @return self
     */
    public function setModel($model)
    {
        $this->model = $model;
        $this->request["model"] = $model;

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
    public function setPrompt($prompt)
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
                        $this->request["system"] = $element->block;
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

        $this->request["prompt"] = $this->prompt;

        return $this;
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
}