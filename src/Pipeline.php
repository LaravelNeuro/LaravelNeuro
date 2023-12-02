<?php
namespace Kbirenheide\L3MA;

use Kbirenheide\L3MA\ApiAdapter;
use Kbirenheide\L3MA\Prompts\Ollama\Prompt;

class Pipeline extends ApiAdapter {

    protected $model;
    protected $prompt;
    protected $system;

    public function setModel($model)
    {
        $this->model = $model;
        $this->request["model"] = $model;

        return $this;
    }

    public function setPrompt($prompt)
    {
        
        if(is_string($prompt))
        {
            $this->prompt = $prompt;
        }
        elseif($prompt instanceof Prompt)
        {
            $this->prompt = '';

            foreach($prompt as $element)
            {
                switch($element->type)
                {
                    case "role":
                        $this->request["system"] .= $element->block;
                        break;
                    default:
                        $this->prompt .= $element->block."\n";
                        break;
                }
                
            }

        }
        else
        {
                throw new \InvalidArgumentException("For this pipeline, the paramater passed to setPrompt should be a string or an array.");
        }

        $this->request["prompt"] = $this->prompt;

        return $this;
    }

    public function getModel($model)
    {
        return $this->model;
    }

    public function getPrompt($prompt)
    {
        return $this->prompt;
    }
}