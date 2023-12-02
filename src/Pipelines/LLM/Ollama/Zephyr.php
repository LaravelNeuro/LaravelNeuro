<?php
namespace Kbirenheide\L3MA\Pipelines\LLM\Ollama;

use Kbirenheide\L3MA\Prompt;
use Kbirenheide\L3MA\Pipeline;

class Zephyr extends Pipeline {

    protected $model;
    protected $api;

    public function __construct()
    {
        $this->model = config('l3ma.models.Zephyr.model');
        $this->request["model"] = $this->model;
        $this->api = config('l3ma.models.Zephyr.api');
        if(empty($this->model))
        {
            throw new \InvalidArgumentException("No model name has been set for this pipeline in the l3ma config file (app/config/l3ma.php).");
        }
        if(empty($this->api))
        {
            throw new \InvalidArgumentException("No api address has been set for this pipeline in the l3ma config file (app/config/l3ma.php).");
        }
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

            foreach($prompt as $key => $element)
            {
                switch($element->type)
                {
                    case "system":
                        $this->prompt .= "\n<|agent|>\n";
                        $this->prompt .= $element->block."</s>\n";
                            if($key == (count($prompt) - 1))
                            {
                                $this->prompt .= "</s>\n";
                            }
                        break;
                    case "user":
                        $this->prompt .= "\n<|user|>\n";
                        $this->prompt .= $element->block."</s>\n";
                        break;
                    case "role":
                        $this->request["system"] .= $element->block;
                        break;
                    default:
                        //
                        break;
                }
                
            }

            if($prompt[count($prompt) - 1]->type == "user")
            {
                $this->prompt .= "\n<|agent|>\n";
            }   

        }
        else
        {
            if (!is_string($prompt)) {
                throw new \InvalidArgumentException("For this pipeline, the paramater passed to setPrompt should be a string or an array.");
            }
        }

        $this->request["prompt"] = $this->prompt;

        return $this;
    }

}