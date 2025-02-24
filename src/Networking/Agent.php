<?php
namespace LaravelNeuro\Networking;

use Generator;
use LaravelNeuro\Networking\Database\Models\NetworkAgent;
use LaravelNeuro\Networking\Database\Models\NetworkUnit;
use LaravelNeuro\Drivers\WebRequests\GuzzleDriver;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Contracts\AiModel\Driver;


use LaravelNeuro\Enums\APItype;

class Agent {

    public string $name;
    protected $model;
    protected $prompt;
    protected $api;
    public Driver $driver;
    public APItype $apiType;
    public string $promptClass;
    public string $pipelineClass;
    public string $role;
    public $outputModel = false;
    public bool $validateOutput = false;
    public int $unit;
    public int $id;

    public function init(int $agentId)
    {
        $agent = NetworkAgent::where('id', $agentId)->first();
        $unit = NetworkUnit::where('id', $agent->unit_id)->first();

        $build = new $agent->pipeline;
        if(!empty($agent->api)) $build->setApi($agent->api);
        if(!empty($agent->model)) $build->setModel($agent->model);

        $agentInstance = (object) [
            "id" => $agent->id,
            "apiType" => $agent->apiType,
            "role" => $agent->role,
            "prompt" => $agent->prompt,
            "promptClass" => $agent->promptClass,
            "name" => $agent->name,
            "unit_id" => $agent->unit_id,
            "unitName" => $unit->name,
            "outputModel" => $agent->outputModel,
            "validateOutput" => $agent->validateOutput,
            "pipeline" => $build,
        ];

        return $agentInstance;
    }

    public function setRole(string $set)
    {
        $this->role = $set;
        $this->driver->setSystemPrompt($set);

        return $this;
    }

    public function setName(string $set)
    {
        $this->name = $set;
        return $this;
    }

    public function setApiType(APItype $set)
    {
        $this->apiType = $set;
        return $this;
    }

    public function setPipelineClass(string $set)
    {
        $this->pipelineClass = $set;
        return $this;
    }

    public function setPromptClass(string $set)
    {
        $this->promptClass = $set;
        return $this;
    }

    public function setOutputModel(string $set)
    {
        $this->outputModel = $set;
        return $this;
    }

    public function validateOutput(bool $set)
    {
        $this->validateOutput = $set;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getRole()
    {
        return $this->role;
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
        $this->prompt = $prompt;

        return $this;
    }

    public function getPrompt()
    {
        return $this->prompt;
    }

    public function setApi($api) : self
    {
        $this->api = $api;

        return $this;
    }

    public function getApi()
    {
        return $this->api;
    }

    public function install(int $unitId)
    {
        $agent = new NetworkAgent;

        $agent->unit_id = $unitId;
        $agent->name = $this->name;
        $agent->model = $this->model;
        $agent->api = $this->api;
        if(!empty($this->apiType ?? null)) $agent->apiType = $this->apiType;
        if(!empty($this->promptClass ?? null)) $agent->promptClass = $this->promptClass;
        $agent->prompt = $this->prompt ?? null;
        $agent->pipeline = $this->pipelineClass;
        $agent->role = $this->role ?? null;
        $agent->outputModel = $this->outputModel ?? null;
        $agent->validateOutput = $this->validateOutput;

        $agent->save();

        return ["agentId" => $agent->id, "agentName" => $agent->name, "agentRole" => $agent->role];

    }

}