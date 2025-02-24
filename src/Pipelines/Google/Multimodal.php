<?php
namespace LaravelNeuro\Pipelines\Google;

use Generator;
use LaravelNeuro\Prompts\SUAprompt;
use LaravelNeuro\Drivers\WebRequests\GuzzleDriver;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Contracts\AiModel\Driver;

class Multimodal implements Pipeline {

    protected $model;
    protected GuzzleDriver $driver;
    protected $prompt;
    protected $accessToken;
    protected $baseApi;

    public function __construct()
    {
        $this->driver = new GuzzleDriver;
        
        $this->prompt = [];

        $this->accessToken = config('laravelneuro.keychain.google');

        $this->baseApi = config('laravelneuro.models.gemini-pro-1-5.api');

        $this->setModel( config('laravelneuro.models.gemini-pro-1-5.model') );

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
            throw new \InvalidArgumentException("No Google Gemini API access token has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        }
    }

    public function getDriver() : Driver
    {
        return $this->driver;
    }

    public function setApi($address, $stream = false) : self
    {
        if(!empty($this->getModel()))
            $model = $this->getModel();
        else
            $model = '{model}';

        if(!empty($this->accessToken))
            $key = $this->accessToken;
        else
            throw new \InvalidArgumentException("No Google Gemini API access token has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");

        if(!$stream)
            $generate = ':generateContent?key=';
        else
            $generate = ':streamGenerateContent?alt=sse&key=';

        $this->driver->setApi($address . '/' . $model . $generate . $key);
        $this->baseApi = $address;

        return $this;
    }

    public function setModel($model) : self
    {
        $this->model = $model;
        $this->setApi($this->baseApi);

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
                if(preg_match('/\[file:([a-zA-Z0-9+\/.-]+)\|([a-zA-Z0-9+\/=]+)\]/', 
                    $element->block, $filepart) && $element->type != "role")
                    {
                        [$drop, $mime, $b64] = $filepart;
                        $element->block = [
                                            "inlineData" => [
                                                "mimeType" => $mime,
                                                "data" => $b64
                                                    ]
                                                ];
                        [$drop, $mime, $b64] = [null, null, null];
                    }
                    else
                    {
                        $element->block = ["text" => $element->block];
                    }

                switch($element->type)
                {
                    case "role":
                        $role = $element->block;
                        break;
                    case "agent":
                        $this->prompt[] = [ "role" => "model", 
                                            "parts" => $element->block
                                          ];
                        break;
                    case "user":
                        $this->prompt[] = [ "role" => "user", 
                                            "parts" => $element->block
                                          ];
                    default:
                        break;
                }   
            }

            if(isset($role))
            {
                $this->driver->modifyRequest("contents", $this->prompt);
                $this->driver->modifyRequest("system_instruction", [
                    "parts" => $role
                ]);
            }
            else
            {
                $this->driver->modifyRequest("contents", $this->prompt);
            }   

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

    public function output() : string
    {
        return $this->text();
    }

    public function text() : string
    {
        $body = $this->driver->output();
        return json_decode((string) $body)->candidates[0]->content->parts[0]->text;
    }

    public function json() : string
    {
        $body = $this->driver->output();
        return json_encode(json_decode($body), JSON_PRETTY_PRINT);
    }

    public function array() : array
    {
        $body = $this->driver->output();
        return json_decode($body, true);
    }

    public function stream() : Generator
    {
        $this->setApi($this->baseApi, true);
        $body = $this->driver->stream();
        foreach($body as $output)
        {
            $output = json_decode($output);
            yield json_encode($output, JSON_PRETTY_PRINT);
        }
    }

    public function streamText() : Generator
    {
        $this->setApi($this->baseApi, true);
        $body = $this->driver->stream();
        foreach($body as $output)
        {
            $output = (object) json_decode($output);
            if(property_exists($output->candidates[0]->content->parts[0], "text")) 
               yield $output->candidates[0]->content->parts[0]->text;  
        }
    }

    public function streamArray() : Generator
    {
        $this->setApi($this->baseApi, true);
        $body = $this->driver->stream();
        foreach($body as $output)
        {
            $output = json_decode($output);
            yield $output;
        }
    }

}