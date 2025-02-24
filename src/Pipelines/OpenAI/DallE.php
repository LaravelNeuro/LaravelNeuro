<?php
namespace LaravelNeuro\Pipelines\OpenAI;

use Generator;
use Illuminate\Support\Str;
use LaravelNeuro\Contracts\AiModel\Driver;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Drivers\WebRequests\GuzzleDriver;
use LaravelNeuro\Prompts\PNSQFprompt;

class DallE implements Pipeline {

    protected $model;
    protected GuzzleDriver $driver;
    protected $prompt;
    protected $accessToken;
    protected $fileType = "png";

    public function __construct()
    {
        $this->driver = new GuzzleDriver;
        
        $this->prompt = [];
        $this->setModel(
                        config('laravelneuro.models.dall-e-2.model')
                        );
        $this->driver->setApi(
                    config('laravelneuro.models.dall-e-2.api')
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
        if($prompt instanceof PNSQFprompt)
        {
            $this->prompt = $prompt->getPrompt();  
            $this->driver->modifyRequest("prompt", $this->prompt);
            $this->driver->modifyRequest("n", $prompt->getNumber());
            $this->driver->modifyRequest("size", $prompt->getSize());
            $this->driver->modifyRequest("quality", $prompt->getQuality());
            $this->driver->modifyRequest("response_format", $prompt->getFormat());
        }
        else
        {
            throw new \Exception("The DallE Pipeline requires a PNSQFprompt class prompt.");
        }
    
        return $this;
    }

    public function getPrompt()
    {
        return $this->prompt;
    }

    public function setFileType(string $type)
    {
        $this->fileType = $type;
        return $this;
    }

    public function output($json = true)
    {
        return $this->store($json);
    }

    public function b64()
    {
        $body = $this->driver->output();
        $images = json_decode($body)->data;
        $images = [];
        foreach($images as $data)
        {
            if(property_exists($data, 'b64_json'))
                {
                    $images[] = $data->b64_json;
                }
        }

        return $images;
    }

    public function raw()
    {
        $body = $this->driver->output();
        $imagedata = json_decode($body)->data;
        $images = [];
        foreach($imagedata as $data)
        {
            if(property_exists($data, 'b64_json'))
                {
                    $images[] = base64_decode($data->b64_json);
                }
            elseif(property_exists($data, 'url'))
                {
                    $images[] = $data->url;
                }
        }
        return $images;
    }

    public function store($json = false)
    {
        $images = $this->raw();
        $fileMetaData = [];
        foreach($images as $image)
        {
            if(!filter_var($image, FILTER_VALIDATE_URL))
            {
                $file = (string) Str::uuid() . '.' . $this->fileType;
                $fileMetaData[] = $this->driver->fileMake($file, $image);
            }
            else
            {
                $fileMetaData[] = ["url" => $image];
            }
        }
        if($json)
            return json_encode($fileMetaData);
        else
            return $fileMetaData;
    }

    public function stream() : Generator
    {
        throw new \Exception("Stream mode is not supported for this pipeline.");
    }

}