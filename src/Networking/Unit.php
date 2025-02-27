<?php
namespace LaravelNeuro\Networking;

use LaravelNeuro\Networking\Agent;
use LaravelNeuro\Networking\Database\Models\NetworkDataSetTemplate;
use LaravelNeuro\Networking\Database\Models\NetworkUnit;
use LaravelNeuro\Enums\UnitReceiver;
use Illuminate\Support\Collection;

/**
 * Represents a unit within a Laravel Neuro Corporation. A unit can have multiple datasets and agents,
 * and defines a default receiver for handling communication. You can think of it as a company department. 
 * The Unit class is responsible for storing configuration data and installing itself into the database 
 * by creating a corresponding NetworkUnit record, along with its associated data set templates and agents.
 *
 * @package LaravelNeuro
 */
class Unit {

    /**
     * The name of the unit.
     *
     * @var string
     */
    public $name;

    /**
     * A short description of the unit.
     *
     * @var string
     */
    public $description;

    /**
     * A collection of dataset configurations associated with the unit.
     *
     * @var Collection
     */
    public $dataSets;

    /**
     * A collection of Agent instances associated with the unit.
     *
     * @var Collection
     */
    public $agents;

    /**
     * The default receiver name for the unit.
     *
     * @var string
     */
    public $defaultReceiver;

    /**
     * The type of the default receiver, as defined by the UnitReceiver enum.
     *
     * @var UnitReceiver
     */
    public UnitReceiver $defaultReceiverType;

    /**
     * Unit constructor.
     *
     * Optionally loads an existing unit (if $load is true) or initializes empty collections for datasets and agents.
     *
     * @param bool $load If true, load an existing unit from the model by unit ID.
     */
    function __construct($load = false)
    {
        if ($load) {
            // load existing Unit from Model by unit id
        } else {
            $this->dataSets = collect([]);
            $this->agents = collect([]);
        } 
    }

    /**
     * Sets the unit's name.
     *
     * @param string $set The name to assign to the unit.
     * @return self Returns the current instance for chaining.
     */
    public function setName(string $set)
    {
        $this->name = $set; 
        return $this;          
    }   
    
    /**
     * Sets the unit's description.
     *
     * @param string $set The description to assign.
     * @return self Returns the current instance for chaining.
     */
    public function setDescription(string $set)
    {
        $this->description = $set; 
        return $this;          
    }  
    
    /**
     * Configures a dataset for the unit.
     *
     * Validates that the provided data, once JSON-encoded, is valid JSON. Adds a dataset configuration,
     * including a name, a completion prompt, and a JSON-encoded structure.
     *
     * @param string $name The name of the dataset.
     * @param string $completion The prompt to use for completion.
     * @param object $data The dataset structure as an object.
     * @return self Returns the current instance for chaining.
     * @throws \InvalidArgumentException If the encoded data is not valid JSON.
     */
    public function configureDataSet(string $name, string $completion, object $data)
    { 
        $structure = json_encode($data, JSON_PRETTY_PRINT);
        if (!json_validate($structure)) {
            throw new \InvalidArgumentException("The second configureDataSet parameter needs to be a valid json string.");
        }
        $this->dataSets->push((object) [
            "name" => $name, 
            "completion" => $completion, 
            "structure" => $structure
        ]);
        return $this;         
    }   
    
    /**
     * Adds an Agent instance to the unit.
     *
     * @param Agent $agent The agent to add.
     * @return self Returns the current instance for chaining.
     */
    public function pushAgent(Agent $agent)
    {
        $this->agents->push($agent);
        return $this;
    }   
    
    /**
     * Sets the default receiver for the unit.
     *
     * Assigns the default receiver type and name.
     *
     * @param UnitReceiver $type The type of the default receiver.
     * @param string $name The name of the default receiver.
     * @return void
     */
    public function setDefaultReceiver(UnitReceiver $type, string $name)
    {
        $this->defaultReceiver = $name;
        $this->defaultReceiverType = $type;
    }   

    /**
     * Retrieves the unit's name.
     *
     * @return string The unit name.
     */
    public function getName()
    {
        return $this->name;          
    }   
    
    /**
     * Retrieves the unit's description.
     *
     * @return string The unit description.
     */
    public function getDescription()
    {
        return $this->description;          
    }   
    
    /**
     * Retrieves a dataset by name.
     *
     * @param string $name The name of the dataset to retrieve.
     * @return mixed The dataset, or null if not found.
     */
    public function getDataSet(string $name)
    { 
        return $this->dataSets->get($name);      
    }   
    
    /**
     * Retrieves the default receiver configuration.
     *
     * Returns an associative array containing the type and name of the default receiver.
     *
     * @param string $type (Unused) Intended receiver type filter.
     * @param string $name (Unused) Intended receiver name filter.
     * @return array An array with keys "type" and "name" for the default receiver.
     */
    public function getDefaultReceiver(string $type, string $name)
    {
        return [
            "type" => $this->defaultReceiverType, 
            "name" => $this->defaultReceiver
        ];
    }  

    /**
     * Installs the unit into the database.
     *
     * Creates a new NetworkUnit record with the unit's properties and associates it with the given corporation ID.
     * Then, for each dataset configured for the unit, a NetworkDataSetTemplate record is created.
     * Finally, each associated Agent is installed, and their IDs are collected.
     *
     * @param mixed $corporation The corporation ID with which this unit is associated.
     * @return array An array containing the unit ID, name, description, and installed agents.
     */
    public function install($corporation)
    {
        $unit = new NetworkUnit;

        $unit->name = $this->name;
        $unit->description = $this->description;
        $unit->defaultReceiver = $this->defaultReceiver;
        $unit->defaultReceiverType = $this->defaultReceiverType;
        $unit->corporation_id = $corporation;

        $unit->save();

        foreach ($this->dataSets as $dataSet) {
            $buildDataSet = new NetworkDataSetTemplate;         
            $buildDataSet->unit_id = $unit->id;
            $buildDataSet->name = $dataSet->name;
            $buildDataSet->completionPrompt = $dataSet->completion;
            $buildDataSet->completionResponse = $dataSet->structure;
            $buildDataSet->save();
        }

        $agents = [];
        foreach ($this->agents as $agent) {
            $agentId = $agent->install($unit->id);
            $agents[] = $agentId;
        }

        return [
            "unitId" => $unit->id, 
            "unitName" => $unit->name, 
            "unitDescription" => $unit->description, 
            "agents" => $agents
        ];
    }
}