<?php
namespace LaravelNeuro\Pipelines\ElevenLabs;

use Generator;
use Illuminate\Support\Str;
use LaravelNeuro\Prompts\IVFSprompt;
use LaravelNeuro\Drivers\WebRequests\GuzzleDriver;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Contracts\AiModel\Driver;

class AudioTTS implements Pipeline {

    protected $model;
    protected GuzzleDriver $driver;
    protected $prompt;
    protected $voice;
    protected $fileType = "mp3";
    protected $accessToken;
 
    public function __construct()
    {
        $this->driver = new GuzzleDriver;
        
        $this->prompt = [];
        
        $this->voice = config('laravelneuro.models.eleven-monolingual-v1.voice');
        $model = config('laravelneuro.models.eleven-monolingual-v1.model');
        $api = config('laravelneuro.models.eleven-monolingual-v1.api');
        $this->accessToken = config('laravelneuro.keychain.elevenlabs');

        $this->setModel($model);
        $this->driver->setApi($api);

        $this->driver->setHeaderEntry("xi-api-key", $this->accessToken);
        $this->driver->setHeaderEntry("Content-Type", "application/json");
        $this->driver->setHeaderEntry("Accept", "audio/mpeg");

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
        $this->driver->modifyRequest("model_id", $model);

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
            if(empty($prompt->getSettings()))
            {
                $prompt->settings([
                "similarity_boost" => 0.5, 
                "stability" => 0.5
                ]);
            }
            $this->prompt = $prompt->getInput();  
            $this->driver->modifyRequest("text", $this->prompt);
            $this->driver->setApi(str_replace("{voice}", ($prompt->getVoice() ?? $this->voice), $this->driver->getApi()));

            $voice_settings = json_decode(json_encode($prompt->getSettings()));
            if(isset($prompt->getSettings()["stability"]))
                $voice_settings->stability = $prompt->getSettings()["stability"];
            if(isset($prompt->getSettings()["similarity_boost"]))
                $voice_settings->similarity_boost = $prompt->getSettings()["similarity_boost"];
            if(isset($prompt->getSettings()["style"]))
                $voice_settings->style = $prompt->getSettings()["style"];
            if(isset($prompt->getSettings()["use_speaker_boost"]))
                $voice_settings->use_speaker_boost = $prompt->getSettings()["use_speaker_boost"];

            $this->driver->modifyRequest("voice_settings", $voice_settings);
        }
        else
        {
            throw new \InvalidArgumentException("Non-IVFS prompts are unlikely to work with Elevenlab's voice endpoint. If you have your own Prompt model, you can apply it by extending this VoiceTTS class and overriding the setPrompt method.");
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

    public function raw()
    {
        $body = $this->driver->output();

        return $body;
    }

    public function store($json = false)
    {
        $audio = $this->raw();
        $file = (string) Str::uuid() . '.' . $this->fileType;
        $fileMetaData = $this->driver->fileMake($file, $audio);
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