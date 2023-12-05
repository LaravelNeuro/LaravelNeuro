<?php
namespace Kbirenheide\LaravelNeuro\Networking;

use Kbirenheide\LaravelNeuro\Networking\Database\Models\NetworkCorporation;
use Kbirenheide\LaravelNeuro\Networking\Unit;

class Incorporate {

    protected $name;
    protected $description;  
    protected $charta;
    protected $InitialReceiver;
    protected $units;

    function __construct()
    {
        $this->units = collect([]);
    }

    private static function validateProperty($object, $propertyName, $expectedType) {
        if (!isset($object->$propertyName)) {
            throw new \Exception("Property '{$propertyName}' is missing.");
        }
        if (gettype($object->$propertyName) !== $expectedType) {
            throw new \Exception("Property '{$propertyName}' is not of type '{$expectedType}'.");
        }
        return $object->$propertyName;
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

    public function setCharta(string $set)
    {
        $this->charta = $set;
        return $this;    
    }

    public function setInitialReceiver($set)
    {
        if(is_string($set) || (is_array($set) && count($set) == 2)) $this->InitialReceiver = $set;
        else throw new \InvalidArgumentException("setInitialReceiver only accepts strings or arrays with exactly two arguments as its set parameter."); 
        return $this;       
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getCharta()
    {
        return $this->charta;
    }

    public function getInitialReceiver()
    {
        return $this->InitialReceiver;
    }

    public function pushUnit(Unit $unit)
    {
        $this->units->push($unit); 
        return $this;  
    }

    public static function installFromJSON($json = false)
    {

            $import = json_decode($json);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON: " . json_last_error_msg());
            }

            $setup = new Incorporate;

            $setup->setName(self::validateProperty($import, "name", "string"))
                  ->setDescription(self::validateProperty($import, "description", "string"))
                  ->setCharta(self::validateProperty($import, "charta", "string"))
                  ->setInitialReceiver(self::validateProperty($import, "initialReceiver", "string"));
            
            foreach(self::validateProperty($import, "units", "array") as $unit)
            {
                $constructUnit = new Unit;
                $constructUnit->setName(self::validateProperty($unit, "name", "string"))
                              ->setDescription(self::validateProperty($unit, "description", "string"));

                $defaultReceiver = self::validateProperty($unit, "defaultReceiver", "object");
                    $defaultReceiverType = self::validateProperty($defaultReceiver, "type", "string");
                    $defaultReceiverName = self::validateProperty($defaultReceiver, "name", "string");

                $constructUnit->setDefaultReceiver($defaultReceiverType, $defaultReceiverName);
                
                foreach(self::validateProperty($unit, "dataSet", "array") as $dataSet)
                {
                    $constructUnit->configureDataSet(
                        self::validateProperty($dataSet, "name", "string"), 
                        self::validateProperty($dataSet, "structure", "string")
                    );
                }

                foreach(self::validateProperty($unit, "agents", "array") as $agent)
                {
                    $constructAgent = new Agent;
                    $constructAgent->setName(self::validateProperty($agent, "name", "string")) 
                    ->setModel(self::validateProperty($agent, "model", "string"))
                    ->setApi(self::validateProperty($agent, "api", "string"))
                    ->setRole(self::validateProperty($agent, "role", "string"))
                    ->setOutputModel(self::validateProperty($agent, "outputModel", "string"))
                    ->validateOutput(self::validateProperty($agent, "validateOutput", "boolean"));

                    $pipe = self::validateProperty($agent, "pipe", "object");
                        $receiverType = self::validateProperty($pipe, "receiverType", "string");
                        $retrieverType = self::validateProperty($pipe, "retrieverType", "string");
                        $retriever = self::validateProperty($pipe, "retriever", "string");

                    $constructAgent->setPipe( 
                        (new Pipe)
                        ->setReceiverType($receiverType)
                        ->setRetrieverType($retrieverType)
                        ->setRetriever($retriever)
                    );

                    $constructUnit->pushAgent($constructAgent);                                  
                }
            }

            return $setup->install();

    }

    public function install()
    {
            $corporation = new NetworkCorporation;
            $corporation->name = $this->name;
            $corporation->description = $this->description;
            $corporation->charta = $this->charta;

            $InitialReceiver = $this->InitialReceiver;

                if(is_array($InitialReceiver)) 
                {
                    $IRunit = $InitialReceiver[0];
                    $IRagent = $InitialReceiver[1];
                } 
                else
                {
                    $IRunit = $InitialReceiver;
                    $IRagent = null;
                }
            
            $corporation->initial_unit = $IRunit;
            $corporation->initial_agent = $IRagent;

            $corporation->save();

            $units = [];
            foreach($this->units as $unit)
            {
                $unitRef = $unit->install($corporation->id);
                $units[] = $unitRef;
            }

            return ["corporationId" => $corporation->id, "corporationName" => $corporation->name, "corporationDescription" => $corporation->description, "units" => $units];
    }

}