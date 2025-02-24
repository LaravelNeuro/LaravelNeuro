<?php
namespace LaravelNeuro\Pipelines\OpenAI;

use Generator;
use LaravelNeuro\Prompts\SUAprompt;
use LaravelNeuro\Contracts\AiModel\Driver;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Drivers\WebRequests\GuzzleDriver;

class ChatCompletion implements Pipeline {

    protected $model;
    protected GuzzleDriver $driver;
    protected $prompt;
    protected $accessToken;

    public function __construct()
    {
        $this->driver = new GuzzleDriver;
        
        $this->prompt = [];
        $this->setModel(
                        config('laravelneuro.models.gpt-3-5-turbo.model')
                        );
        $this->driver->setApi(
                    config('laravelneuro.models.gpt-3-5-turbo.api')
                    );
        $this->accessToken = config('laravelneuro.keychain.openai');

        $this->driver->setHeaderEntry("Authorization", "Bearer " . $this->accessToken);
        $this->driver->setHeaderEntry("Content-Type", "application/json");

        if(empty($this->model))
        {
            throw new \InvalidArgumentException("No model name has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        }
        if(empty($this->api))
        {
            throw new \InvalidArgumentException("No api address has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        }
        if(empty($this->accessToken))
        {
            throw new \InvalidArgumentException("No OpenAI access token has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        }
    }

    public function getDriver() : Driver
    {
        return $this->driver;
    }

    public function setModel($model) : self
    {
        $this->model = $model;
        $this->driver->setModel($model);
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setPrompt($prompt) : self
    {
        if($prompt instanceof SUAprompt)
        {
            $this->prompt = [];

            foreach($prompt as $element)
            {
                switch($element->type)
                {
                    case "role":
                        $role = [["role" => "system", 
                                 "content" => $element->block]];
                        break;
                    case "agent":
                        $this->prompt[] = ["role" => "assistant", 
                                            "content" => $element->block];
                        break;
                    case "user":
                        $this->prompt[] = ["role" => "user", 
                                            "content" => $element->block];
                    default:
                        break;
                }   
            }

            $this->driver->modifyRequest("messages", array_merge($role, $this->prompt));

        }
        else
        {
                throw new \InvalidArgumentException("For this pipeline, the paramater passed to setPrompt should be a SUAprompt Object");
        }

        return $this;
    }

    public function getPrompt()
    {
        return $this->prompt;
    }

    public function output()
    {
        return $this->text();
    }

    public function text()
    {
        $body = $this->driver->output();
        return json_decode((string) $body)->choices[0]->message->content;
    }

    public function json()
    {
        $body = $this->driver->output();
        return json_encode(json_decode($body), JSON_PRETTY_PRINT);
    }

    public function array()
    {
        $body = $this->driver->output();
        return json_decode($body, true);
    }

    public function stream() : Generator
    {
        $this->driver->modifyRequest("stream", true);
        yield $this->driver->stream();
    }

    public function streamText() : Generator
    {
        foreach($this->stream() as $output)
        {
            $output = (object) json_decode($output);
            if(property_exists($output->choices[0]->delta, "content")) 
               yield $output->choices[0]->delta->content;  
        }
    }

    public function streamJson() : Generator
    {
        foreach($this->stream() as $output)
        {
            $output = json_decode($output);
            yield json_encode($output, JSON_PRETTY_PRINT);
        }
    }

    public function streamArray() : Generator
    {
        foreach($this->stream() as $output)
        {
            $output = json_decode($output);
            yield $output;
        }
    }

}