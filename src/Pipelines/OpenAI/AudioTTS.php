<?php
namespace LaravelNeuro\Pipelines\OpenAI;

use LaravelNeuro\Pipeline;
use LaravelNeuro\Prompts\IVFSprompt;
use Illuminate\Support\Str;

class AudioTTS extends Pipeline {

    protected $model;
    protected $fileType;
    protected $accessToken;

    public function __construct()
    {
        $this->prompt = [];
        $this->setModel(
                        config('laravelneuro.models.tts-1.model')
                        );
        $this->setApi(
                    config('laravelneuro.models.tts-1.api')
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
        if($prompt instanceof IVFSprompt)
        {
            $this->prompt = $prompt->getInput();  
            $this->request["input"] = $this->prompt;
            $this->request["voice"] = $prompt->getVoice();
            $this->request["response_format"] = $prompt->getFormat();
            $this->fileType = $prompt->getFormat();
            if(isset($prompt->getSettings()["speed"]))
                $this->request["speed"] = $prompt->getQuality();
        }
        else
        {
            throw new \InvalidArgumentException("Non-IVFS prompts are unlikely to work with OpenAI's voice endpoint. If you have your own Prompt model, you can apply it by extending this VoiceTTS class and overriding the setPrompt method.");
        }
    
        return $this;
    }

    public function output($json = true)
    {
        return $this->store($json);
    }

    public function store($json = false)
    {
        $audio = parent::output();
        $file = (string) Str::uuid() . '.' . $this->fileType;
        $fileMetaData = $this->fileMake($file, $audio);
        if($json)
            return json_encode($fileMetaData);
        else
            return $fileMetaData;
    }

    public function raw()
    {
        $audio = parent::output();
        return $audio;
    }

}