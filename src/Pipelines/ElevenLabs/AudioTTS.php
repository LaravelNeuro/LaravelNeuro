<?php
namespace LaravelNeuro\LaravelNeuro\Pipelines\ElevenLabs;

use LaravelNeuro\LaravelNeuro\Pipeline;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use LaravelNeuro\LaravelNeuro\Prompts\IVFSprompt;
use Illuminate\Support\Str;

class AudioTTS extends Pipeline {

    protected string $model;
    protected string $voice;
    protected string $api;
    protected string $fileType = "mp3";
    protected string $accessToken;

    public function __construct()
    {
        $this->prompt = [];
        
        $this->voice = config('laravelneuro.models.eleven-monolingual-v1.voice');
        $model = config('laravelneuro.models.eleven-monolingual-v1.model');
        $api = config('laravelneuro.models.eleven-monolingual-v1.api');
        $this->accessToken = config('laravelneuro.keychain.elevenlabs');

        $this->setModel($model);
        $this->setApi($api);

        $this->setHeaderEntry("xi-api-key", $this->accessToken);
        $this->setHeaderEntry("Content-Type", "application/json");
        $this->setHeaderEntry("Accept", "audio/mpeg");

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

    public function setModel($model)
    {
        $this->model = $model;
        $this->request["model_id"] = $model;

        return $this;
    }

    public function setPrompt($prompt)
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
            $this->request["text"] = $this->prompt;
            $this->setApi(str_replace("{voice}", ($prompt->getVoice() ?? $this->voice), $this->api));

            $this->request["voice_settings"] = json_decode(json_encode($prompt->getSettings()));

            if(isset($prompt->getSettings()["stability"]))
            $this->request["voice_settings"]->stability = $prompt->getSettings()["stability"];

            if(isset($prompt->getSettings()["similarity_boost"]))
                $this->request["voice_settings"]->similarity_boost = $prompt->getSettings()["similarity_boost"];
            
            if(isset($prompt->getSettings()["style"]))
                $this->request["voice_settings"]->style = $prompt->getSettings()["style"];
            
            if(isset($prompt->getSettings()["use_speaker_boost"]))
                $this->request["voice_settings"]->use_speaker_boost = $prompt->getSettings()["use_speaker_boost"];
        }
        else
        {
            throw new \InvalidArgumentException("Non-IVFS prompts are unlikely to work with Elevenlab's voice endpoint. If you have your own Prompt model, you can apply it by extending this VoiceTTS class and overriding the setPrompt method.");
        }
        
        return $this;
    }

    public function output($json = true)
    {
        return $this->store($json);
    }

    public function raw()
    {
        $body = parent::output();

        return $body;
    }

    public function store($json = false)
    {
        $audio = $this->raw();
        $file = (string) Str::uuid() . '.' . $this->fileType;
        $fileMetaData = $this->fileMake($file, $audio);
        if($json)
            return json_encode($fileMetaData);
        else
            return $fileMetaData;
    }

}