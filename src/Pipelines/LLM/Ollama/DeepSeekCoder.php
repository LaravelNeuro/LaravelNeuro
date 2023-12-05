<?php
namespace Kbirenheide\LaravelNeuro\Pipelines\LLM\Ollama;

use Kbirenheide\LaravelNeuro\Prompts\Ollama\Prompt;
use Kbirenheide\LaravelNeuro\Pipeline;

class DeepSeekCoder extends Pipeline {

    protected $model;
    protected $api;

    public function __construct()
    {
        $this->model = config('LaravelNeuro.models.DeepSeekCoder.model');
        $this->request["model"] = $this->model;
        $this->api = config('LaravelNeuro.models.DeepSeekCoder.api');
        if(empty($this->model))
        {
            throw new \InvalidArgumentException("No model name has been set for this pipeline in the LaravelNeuro config file (app/config/LaravelNeuro.php).");
        }
        if(empty($this->api))
        {
            throw new \InvalidArgumentException("No api address has been set for this pipeline in the LaravelNeuro config file (app/config/LaravelNeuro.php).");
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
            $purpose = "You are an AI programming assistant, utilizing the DeepSeek Coder model, developed by DeepSeek Company, and you only answer questions related to computer science. For politically sensitive questions, security and privacy issues, and other non-computer science questions, you will refuse to answer.\n";

            foreach($prompt as $element)
            {
                switch($element->type)
                {
                    case "system":
                        $this->prompt .= "\n### Instruction:\n";
                        $this->prompt .= $element->block."\n";
                        break;
                    case "user":
                        $this->prompt .= "\n### Response:\n";
                        $this->prompt .= $element->block."\n";
                        break;
                    case "role":
                        $this->request["system"] = $element->block;
                    default:
                        $this->prompt .= "\n### Instruction:\n";
                        $this->prompt .= $element->block."\n";
                        break;
                }
                
            }
            
            if($prompt[count($prompt) - 1]->type == "system")
            {
                $this->prompt .= "\n### Response:\n";
            }

            $this->prompt = $purpose . $this->prompt;

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