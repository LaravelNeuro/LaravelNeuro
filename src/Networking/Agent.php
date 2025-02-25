<?php
namespace LaravelNeuro\Networking;

use LaravelNeuro\Networking\Database\Models\NetworkAgent;
use LaravelNeuro\Networking\Database\Models\NetworkUnit;
use LaravelNeuro\Contracts\AiModel\Driver;
use LaravelNeuro\Enums\APItype;

/**
 * Class Agent
 *
 * Represents an AI agent within a LaravelNeuro Corporation. An Agent encapsulates
 * settings for connecting to an AI service, including model, API endpoint, prompt,
 * pipeline class, and role. This class provides methods for configuring these properties,
 * initializing an agent from the database, and installing the agent into the system.
 *
 * @package LaravelNeuro
 */
class Agent {

    /**
     * The agent's name.
     *
     * @var string
     */
    public string $name;

    /**
     * The model identifier for the agent.
     *
     * @var mixed
     */
    protected $model;

    /**
     * The prompt data for the agent.
     *
     * @var mixed
     */
    protected $prompt;

    /**
     * The API endpoint for the agent.
     *
     * @var mixed
     */
    protected $api;

    /**
     * The driver used for making API requests.
     *
     * @var Driver
     */
    public Driver $driver;

    /**
     * The API type, as defined by the APItype enum.
     *
     * @var APItype
     */
    public APItype $apiType;

    /**
     * The fully-qualified prompt class name.
     *
     * @var string
     */
    public string $promptClass;

    /**
     * The fully-qualified pipeline class name.
     *
     * @var string
     */
    public string $pipelineClass;

    /**
     * The role of the agent.
     *
     * @var string
     */
    public string $role;

    /**
     * The output model configuration (if any).
     *
     * @var mixed
     */
    public $outputModel = false;

    /**
     * Flag indicating whether output should be validated.
     *
     * @var bool
     */
    public bool $validateOutput = false;

    /**
     * The associated unit ID.
     *
     * @var int
     */
    public int $unit;

    /**
     * The agent's database ID.
     *
     * @var int
     */
    public int $id;

    /**
     * Sets the role for the agent.
     *
     * @param string $set The role to assign.
     * @return self Returns the current instance for method chaining.
     */
    public function setRole(string $set)
    {
        $this->role = $set;
        return $this;
    }

    /**
     * Sets the name of the agent.
     *
     * @param string $set The agent's name.
     * @return self Returns the current instance for method chaining.
     */
    public function setName(string $set)
    {
        $this->name = $set;
        return $this;
    }

    /**
     * Sets the API type for the agent.
     *
     * @param APItype $set The API type (as an APItype enum instance).
     * @return self Returns the current instance for method chaining.
     */
    public function setApiType(APItype $set)
    {
        $this->apiType = $set;
        return $this;
    }

    /**
     * Sets the pipeline class for the agent.
     *
     * @param string $set The fully-qualified pipeline class name.
     * @return self Returns the current instance for method chaining.
     */
    public function setPipelineClass(string $set)
    {
        $this->pipelineClass = $set;
        return $this;
    }

    /**
     * Sets the prompt class for the agent.
     *
     * @param string $set The fully-qualified prompt class name.
     * @return self Returns the current instance for method chaining.
     */
    public function setPromptClass(string $set)
    {
        $this->promptClass = $set;
        return $this;
    }

    /**
     * Sets the output model configuration for the agent.
     *
     * @param string $set The output model identifier.
     * @return self Returns the current instance for method chaining.
     */
    public function setOutputModel(string $set)
    {
        $this->outputModel = $set;
        return $this;
    }

    /**
     * Sets whether the agent should validate output.
     *
     * @param bool $set True to enable output validation, false otherwise.
     * @return self Returns the current instance for method chaining.
     */
    public function validateOutput(bool $set)
    {
        $this->validateOutput = $set;
        return $this;
    }

    /**
     * Retrieves the agent's name.
     *
     * @return string The agent's name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Retrieves the agent's role.
     *
     * @return string The agent's role.
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Sets the model identifier for the agent.
     *
     * @param mixed $model The model identifier.
     * @return self Returns the current instance for method chaining.
     */
    public function setModel($model) : self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Retrieves the agent's model identifier.
     *
     * @return mixed The model identifier.
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the prompt for the agent.
     *
     * @param mixed $prompt The prompt data.
     * @return self Returns the current instance for method chaining.
     */
    public function setPrompt($prompt) : self
    {
        $this->prompt = $prompt;
        return $this;
    }

    /**
     * Retrieves the prompt data for the agent.
     *
     * @return mixed The prompt.
     */
    public function getPrompt()
    {
        return $this->prompt;
    }

    /**
     * Sets the API endpoint for the agent.
     *
     * @param mixed $api The API endpoint.
     * @return self Returns the current instance for method chaining.
     */
    public function setApi($api) : self
    {
        $this->api = $api;
        return $this;
    }

    /**
     * Retrieves the API endpoint for the agent.
     *
     * @return mixed The API endpoint.
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * Initializes an agent instance from the database.
     *
     * Loads the agent record with the given ID from the NetworkAgent model, retrieves the associated unit,
     * creates an instance of the agent's pipeline (using the stored pipeline class), and returns an object
     * containing various properties of the agent.
     *
     * @param int $agentId The ID of the agent to initialize.
     * @return object An object containing agent properties: id, apiType, role, prompt, promptClass, name,
     *                unit_id, unitName, outputModel, validateOutput, and the pipeline instance.
     */
    public function init(int $agentId)
    {
        $agent = NetworkAgent::where('id', $agentId)->first();
        $unit = NetworkUnit::where('id', $agent->unit_id)->first();

        $build = new $agent->pipeline;
        if (!empty($agent->api)) {
            $build->setApi($agent->api);
        }
        if (!empty($agent->model)) {
            $build->setModel($agent->model);
        }

        $agentInstance = (object)[
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

    /**
     * Installs the agent into the system.
     *
     * Creates a new NetworkAgent record using the agent's configuration, including model, API, pipeline class,
     * role, prompt, output model, and validation settings, and saves it to the database.
     * Returns an array with the agent's ID, name, and role.
     *
     * @param int $unitId The ID of the unit to which the agent belongs.
     * @return array An array containing: agentId, agentName, and agentRole.
     */
    public function install(int $unitId)
    {
        $agent = new NetworkAgent;

        $agent->unit_id = $unitId;
        $agent->name = $this->name;
        $agent->model = $this->model;
        $agent->api = $this->api;
        if (!empty($this->apiType ?? null)) {
            $agent->apiType = $this->apiType;
        }
        if (!empty($this->promptClass ?? null)) {
            $agent->promptClass = $this->promptClass;
        }
        $agent->prompt = $this->prompt ?? null;
        $agent->pipeline = $this->pipelineClass;
        $agent->role = $this->role ?? null;
        $agent->outputModel = $this->outputModel ?? null;
        $agent->validateOutput = $this->validateOutput;

        $agent->save();

        return [
            "agentId" => $agent->id,
            "agentName" => $agent->name,
            "agentRole" => $agent->role
        ];
    }
}