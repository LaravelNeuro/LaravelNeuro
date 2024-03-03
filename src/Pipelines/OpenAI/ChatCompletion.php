<?php
namespace LaravelNeuro\LaravelNeuro\Pipelines\OpenAI;

use LaravelNeuro\LaravelNeuro\Prompts\SUAprompt;
use GuzzleHttp\Exception\GuzzleException;
use LaravelNeuro\LaravelNeuro\Pipeline;

class ChatCompletion extends Pipeline {

    protected string $model;
    protected string $api;
    protected string $accessToken;

    public function __construct()
    {
        $this->prompt = [];
        $this->setModel(
                        config('laravelneuro.models.gpt-3-5-turbo.model')
                        );
        $this->setApi(
                    config('laravelneuro.models.gpt-3-5-turbo.api')
                    );
        $this->accessToken = config('laravelneuro.keychain.openai');

        $this->setHeaderEntry("Authorization", "Bearer " . $this->accessToken);
        $this->setHeaderEntry("Content-Type", "application/json");

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

    public function setPrompt($prompt)
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

            $this->request["messages"] = array_merge($role, $this->prompt);

        }
        else
        {
                throw new \InvalidArgumentException("For this pipeline, the paramater passed to setPrompt should be a SUAprompt Object");
        }

        return $this;
    }

    public function output()
    {
        return $this->text();
    }

    public function text()
    {
        $body = parent::output();
        return json_decode((string) $body)->choices[0]->message->content;
    }

    public function json()
    {
        $body = parent::output();
        return json_encode(json_decode($body), JSON_PRETTY_PRINT);
    }

    public function array()
    {
        $body = parent::output();
        return json_decode($body, true);
    }

    public function streamText()
    {
        $this->request["stream"] = true;
        $body = parent::stream();
        foreach($body as $output)
        {
            $output = (object) json_decode($output);
            if(property_exists($output->choices[0]->delta, "content")) 
               yield $output->choices[0]->delta->content;  
        }
    }

    public function streamJson()
    {
        $this->request["stream"] = true;
        $body = parent::stream();
        foreach($body as $output)
        {
            $output = json_decode($output);
            yield json_encode($output, JSON_PRETTY_PRINT);
        }
    }

    public function streamArray()
    {
        $this->request["stream"] = true;
        $body = parent::stream();
        foreach($body as $output)
        {
            $output = json_decode($output);
            yield $output;
        }
    }

}