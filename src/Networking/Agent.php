<?php
namespace LaravelNeuro\LaravelNeuro\Networking;

use LaravelNeuro\LaravelNeuro\Pipeline;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkAgent;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkUnit;

use LaravelNeuro\LaravelNeuro\Enums\APIprovider;
use LaravelNeuro\LaravelNeuro\Enums\APItype;

class Agent extends Pipeline {

    public string $name;
    public APItype $apiType;
    public string $promptClass;
    public APIprovider $apiProvider;
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
            "apiProvider" => $agent->apiProvider,
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
        $this->request["system"] = $set;

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

    public function setApiProvider(APIprovider $set)
    {
        $this->apiProvider = $set;
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
        return $this->role;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function install(int $unitId)
    {
        $agent = new NetworkAgent;

        $agent->unit_id = $unitId;
        $agent->name = $this->name;
        $agent->model = $this->model;
        $agent->api = $this->api;
        if(!empty($this->apiType ?? null)) $agent->apiType = $this->apiType;
        $agent->apiProvider = $this->apiProvider;
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