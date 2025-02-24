<?php
namespace LaravelNeuro\Pipelines\OpenAI;

use Illuminate\Support\Str;
use LaravelNeuro\Pipeline;
use LaravelNeuro\Prompts\PNSQFprompt;
use PHPUnit\Framework\Constraint\ObjectHasProperty;

class DallE extends Pipeline {

    protected $model;
    protected $accessToken;
    protected $fileType = "png";

    public function __construct()
    {
        $this->prompt = [];
        $this->setModel(
                        config('laravelneuro.models.dall-e-2.model')
                        );
        $this->setApi(
                    config('laravelneuro.models.dall-e-2.api')
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

    public function setPrompt($prompt) : self
    {
        if($prompt instanceof PNSQFprompt)
        {
            $this->prompt = $prompt->getPrompt();  
            $this->request["prompt"] = $this->prompt;
            $this->request["n"] = $prompt->getNumber();
            $this->request["size"] = $prompt->getSize();
            $this->request["quality"] = $prompt->getQuality();
            $this->request["response_format"] = $prompt->getFormat();
        }
        else
        {
            throw new \Exception("The DallE Pipeline requires a PNSQFprompt class prompt.");
        }
    
        return $this;
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
        $body = parent::output();
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
        $body = parent::output();
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
                $fileMetaData[] = $this->fileMake($file, $image);
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

}