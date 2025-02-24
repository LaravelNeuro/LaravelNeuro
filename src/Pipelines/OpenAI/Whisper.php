<?php
namespace LaravelNeuro\Pipelines\OpenAI;

use Generator;
use GuzzleHttp\Psr7;
use LaravelNeuro\Prompts\FSprompt;
use LaravelNeuro\Enums\RequestType;
use LaravelNeuro\Contracts\AiModel\Driver;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Drivers\WebRequests\GuzzleDriver;

class Whisper implements Pipeline {

    protected $model;
    protected GuzzleDriver $driver;
    protected $prompt;
    protected $accessToken;

    public function __construct()
    {
        $this->driver = new GuzzleDriver;
        
        $this->prompt = [];
        $this->setModel(
                        config('laravelneuro.models.whisper-1.model')
                        );
        $this->driver->setApi(
                    config('laravelneuro.models.whisper-1.api')
                    );
        $this->accessToken = config('laravelneuro.keychain.openai');

        $this->driver->setHeaderEntry("Authorization", "Bearer " . $this->accessToken);

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
        if($prompt instanceof FSprompt)
        {
            $this->driver->setRequestType(RequestType::MULTIPART);
            $this->driver->modifyRequest("messages", [
                                'name'     => 'file',
                                'contents' => Psr7\Utils::tryFopen($prompt->getFile(), "r")
                            ]);
            $this->driver->modifyRequest("messages", [
                                'name'     => 'model',
                                'contents' => $this->getModel()
                            ]);
        }
        else
        {
            throw new \InvalidArgumentException("Non-IVFS prompts are unlikely to work with OpenAI's voice endpoint. If you have your own Prompt model, you can apply it by extending this VoiceTTS class and overriding the setPrompt method.");
        }
    
        return $this;
    }

    public function getPrompt()
    {
        return $this->prompt;
    }

    public function output()
    {
        $output = $this->driver->output();
        return json_decode($output)->text;
    }

    public function stream() : Generator
    {
        throw new \Exception("Stream mode is not supported for this pipeline.");
    }

}