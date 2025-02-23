<?php
namespace LaravelNeuro\Networking;

use LaravelNeuro\Networking\Agent;
use LaravelNeuro\Networking\Database\Models\NetworkDataSetTemplate;
use LaravelNeuro\Networking\Database\Models\NetworkUnit;

use LaravelNeuro\Enums\UnitReceiver;

class Unit {

    public $name;
    public $description;
    public $dataSets;
    public $agents;
    public $defaultReceiver;
    public UnitReceiver $defaultReceiverType;

    function __construct($load = false)
    {
        if($load)
        {
            // load existing Unit from Model by unit id
        }
        else
        {
            $this->dataSets = collect([]);
            $this->agents = collect([]);
        } 
    }

    public function setName(string $set)   
    {
        $this->name = $set; 
        return $this;          
    }   
    
    public function setDescription(string $set)   
    {
        $this->description = $set; 
        return $this;          
    }  
    
    public function configureDataSet(string $name, string $completion, object $data)   
    { 
        $structure = json_encode($data, JSON_PRETTY_PRINT);
        if(!json_validate($structure)) throw new \InvalidArgumentException("The second configureDataSet parameter needs to be a valid json string.");
        $this->dataSets->push((object) ["name" => $name, "completion" => $completion, "structure" => $structure]);
        return $this;         
    }   
    
    public function pushAgent(Agent $agent)   
    {
        $this->agents->push($agent);
        return $this;
    }   
    
    public function setDefaultReceiver(UnitReceiver $type, string $name)   
    {
        $this->defaultReceiver = $name;
        $this->defaultReceiverType = $type;
    }   

    public function getName()   
    {
        return $this->name;          
    }   
    
    public function getDescription()   
    {
        return $this->description;          
    }   
    
    public function getDataSet(string $name)   
    { 
        return $this->dataSets->get($name);      
    }   
    
    public function getDefaultReceiver(string $type, string $name)   
    {
        return ["type" => $this->defaultReceiverType, "name" => $this->defaultReceiver];
    }  

    public function install($corporation)
    {
        $unit = new NetworkUnit;

        $unit->name = $this->name;
        $unit->description = $this->description;
        $unit->defaultReceiver = $this->defaultReceiver;
        $unit->defaultReceiverType = $this->defaultReceiverType;
        $unit->corporation_id = $corporation;

        $unit->save();

        foreach($this->dataSets as $dataSet)
        {
            $buildDataSet = new NetworkDataSetTemplate;         
            $buildDataSet->unit_id = $unit->id;
            $buildDataSet->name = $dataSet->name;
            $buildDataSet->completionPrompt = $dataSet->completion;
            $buildDataSet->completionResponse = $dataSet->structure;
            $buildDataSet->save();
        }

        $agents = [];
        foreach($this->agents as $agent)
        {
            $agentId = $agent->install($unit->id);
            $agents[] = $agentId;
        }

        return ["unitId" => $unit->id, "unitName" => $unit->name, "unitDescription" => $unit->description, "agents" => $agents];
    }

}