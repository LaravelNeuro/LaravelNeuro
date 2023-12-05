<?php
namespace Kbirenheide\LaravelNeuro\Networking;

use Kbirenheide\LaravelNeuro\Pipeline;
use Kbirenheide\LaravelNeuro\Networking\Pipe;
use Kbirenheide\LaravelNeuro\Networking\Database\Models\NetworkAgent;

class Agent extends Pipeline {

    public $name;
    public $model;
    public $api;
    public $role;
    public $outputModel;
    public $validateOutput;
    public $pipeReceiverType;
    public $pipeRetrieverType;
    public $pipeRetriever;

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

    public function setPipe(Pipe $set)
    {
        $this->pipeReceiverType = $set->receiverType;
        $this->pipeRetrieverType = $set->retrieverType;
        $this->pipeRetriever = $set->retriever;
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

        $agent->unit = $unitId;
        $agent->name = $this->name;
        $agent->model = $this->model;
        $agent->api = $this->api;
        $agent->role = $this->role;
        $agent->outputModel = $this->outputModel;
        $agent->validateOutput = $this->validateOutput;
        $agent->pipeReceiverType = $this->pipeReceiverType;
        $agent->pipeRetrieverType = $this->pipeRetrieverType;
        $agent->pipeRetriever = $this->pipeRetriever;

        $agent->save();

        return ["agentId" => $agent->id, "agentName" => $agent->name, "agentRole" => $agent->role];

    }

}