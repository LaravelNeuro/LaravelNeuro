<?php
namespace Kbirenheide\LaravelNeuro\Networking;

use Kbirenheide\LaravelNeuro\Networking\Agent;
use Kbirenheide\LaravelNeuro\Networking\Database\Models\NetworkDataSet;
use Kbirenheide\LaravelNeuro\Networking\Database\Models\NetworkUnit;

class Unit {

    public $name;
    public $description;
    public $dataSets;
    public $agents;
    public $defaultReceiver;
    public $defaultReceiverType;

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
    
    public function configureDataSet(string $name, string $data)   
    { 
        if(!json_validate($data)) throw new \InvalidArgumentException("The second configureDataSet parameter needs to be a valid json string.");
        $this->dataSets->push([$name => $data]);
        return $this;         
    }   
    
    public function pushAgent(Agent $agent)   
    {
        $this->agents->push($agent);
        return $this;
    }   
    
    public function setDefaultReceiver(string $type, string $name)   
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
        $unit->defaultRetrieverType = $this->defaultReceiverType;
        $unit->corporation = $corporation;

        $unit->save();

        foreach($this->dataSets as $name => $dataSet)
        {
            $buildDataSet = new NetworkDataSet;
            $buildDataSet->unit = $unit->id;
            $buildDataSet->name = $name;
            $buildDataSet->data = $dataSet;
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