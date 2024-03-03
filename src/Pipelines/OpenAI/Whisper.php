<?php
namespace LaravelNeuro\LaravelNeuro\Pipelines\OpenAI;

use LaravelNeuro\LaravelNeuro\Pipeline;
use LaravelNeuro\LaravelNeuro\Prompts\FSprompt;
use LaravelNeuro\LaravelNeuro\Enums\RequestType;
use GuzzleHttp\Psr7;

class Whisper extends Pipeline {

    protected $model;
    protected $accessToken;

    public function __construct()
    {
        $this->prompt = [];
        $this->setModel(
                        config('laravelneuro.models.whisper-1.model')
                        );
        $this->setApi(
                    config('laravelneuro.models.whisper-1.api')
                    );
        $this->accessToken = config('laravelneuro.keychain.openai');

        $this->setHeaderEntry("Authorization", "Bearer " . $this->accessToken);

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
        if($prompt instanceof FSprompt)
        {
            $this->setRequestType(RequestType::MULTIPART);
            $this->request = [
                [
                    'name'     => 'file',
                    'contents' => Psr7\Utils::tryFopen($prompt->getFile(), "r")
                ],
                [
                    'name'     => 'model',
                    'contents' => $this->getModel()
                ],
            ];
        }
        else
        {
            throw new \InvalidArgumentException("Non-IVFS prompts are unlikely to work with OpenAI's voice endpoint. If you have your own Prompt model, you can apply it by extending this VoiceTTS class and overriding the setPrompt method.");
        }
    
        return $this;
    }

    public function output()
    {
        $output = parent::output();
        return json_decode($output)->text;
    }

}