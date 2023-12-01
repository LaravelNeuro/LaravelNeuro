<?php
namespace Kbirenheide\LaravelAi;

use Kbirenheide\LaravelAI\ApiAdapter;

class Pipeline extends ApiAdapter {

    protected $model;
    protected $prompt;

    public function setModel($model)
    {
        $this->model = $model;
        $this->request["model"] = $model;

        return $this;
    }

    public function setPrompt($prompt)
    {
        $this->prompt = $prompt;
        $this->request["prompt"] = $prompt;

        return $this;
    }

    public function getModel($model)
    {
        return $this->model;
    }

    public function getPrompt($prompt)
    {
        return $this->prompt;
    }
}