<?php
namespace LaravelNeuro\Pipelines\OpenAI;

use Generator;
use Illuminate\Support\Str;
use LaravelNeuro\Prompts\IVFSprompt;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Contracts\AiModel\Driver;
use LaravelNeuro\Drivers\WebRequests\GuzzleDriver;

class AudioTTS implements Pipeline {

    protected $model;
    protected GuzzleDriver $driver;
    protected $prompt;
    protected $fileType;
    protected $accessToken;

    public function __construct()
    {
        $api = config('laravelneuro.models.tts-1.api');
        $model = config('laravelneuro.models.tts-1.model');

        $this->driver = new GuzzleDriver;
        
        $this->prompt = [];
        $this->setModel($model);
        $this->driver->setApi($api);
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
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setPrompt($prompt) : self
    {
        if($prompt instanceof IVFSprompt)
        {
            $this->prompt = $prompt->getInput();  
            $this->driver->modifyRequest("input", $this->prompt);
            $this->driver->modifyRequest("voice", $prompt->getVoice());
            $this->driver->modifyRequest("response_format", $prompt->getFormat());

            $this->fileType = $prompt->getFormat();
            if(isset($prompt->getSettings()["speed"]))
                $this->driver->modifyRequest("speed", $prompt->getQuality());
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

    public function output($json = true)
    {
        return $this->store($json);
    }

    public function store($json = false)
    {
        $audio = $this->driver->output();
        $file = (string) Str::uuid() . '.' . $this->fileType;
        $fileMetaData = $this->driver->fileMake($file, $audio);
        if($json)
            return json_encode($fileMetaData);
        else
            return $fileMetaData;
    }

    public function raw()
    {
        $audio = $this->driver->output();
        return $audio;
    }

    public function stream() : Generator
    {
        throw new \Exception("Stream mode is not supported for this pipeline.");
    }

}