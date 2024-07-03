<?php
namespace LaravelNeuro\LaravelNeuro\Pipelines\Google;

use LaravelNeuro\LaravelNeuro\Prompts\SUAprompt;
use LaravelNeuro\LaravelNeuro\Pipeline;

class Multimodal extends Pipeline {

    protected $model;
    protected $accessToken;
    protected $baseApi;

    public function __construct()
    {
        $this->prompt = [];

        $this->accessToken = config('laravelneuro.keychain.google');

        $this->baseApi = config('laravelneuro.models.gemini-pro-1-5.api');

        $this->setModel( config('laravelneuro.models.gemini-pro-1-5.model') );

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
            throw new \InvalidArgumentException("No Google Gemini API access token has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        }
    }

    public function setApi($address, $stream = false)
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

        $this->api = $address . '/' . $model . $generate . $key;
        $this->baseApi = $address;

        return $this;
    }

    public function setModel($model)
    {
        $this->model = $model;
        $this->setApi($this->baseApi);

        return $this;
    }

    public function setPrompt($prompt)
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
                $promptMask = [
                    "contents" => 
                            $this->prompt
                    ,
                    "system_instruction" => [
                        "parts" => $role
                    ]
                    ];
            }
            else
            {
                $promptMask = [
                        "contents" => 
                            $this->prompt                       
                    ];
            }

            $this->request = $promptMask;

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
        return json_decode((string) $body)->candidates[0]->content->parts[0]->text;
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
        $this->setApi($this->baseApi, true);
        $body = parent::stream();
        foreach($body as $output)
        {
            $output = (object) json_decode($output);
            if(property_exists($output->candidates[0]->content->parts[0], "text")) 
               yield $output->candidates[0]->content->parts[0]->text;  
        }
    }

    public function streamJson()
    {
        $this->setApi($this->baseApi, true);
        $body = parent::stream();
        foreach($body as $output)
        {
            $output = json_decode($output);
            yield json_encode($output, JSON_PRETTY_PRINT);
        }
    }

    public function streamArray()
    {
        $this->setApi($this->baseApi, true);
        $body = parent::stream();
        foreach($body as $output)
        {
            $output = json_decode($output);
            yield $output;
        }
    }

}