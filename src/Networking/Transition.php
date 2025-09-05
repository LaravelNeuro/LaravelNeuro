<?php
namespace LaravelNeuro\Networking;

use Illuminate\Support\Collection;
use LaravelNeuro\Networking\TuringHead;
use LaravelNeuro\Networking\Agent;
use LaravelNeuro\Networking\Tracable;
use LaravelNeuro\Networking\Database\Models\NetworkCorporation;
use LaravelNeuro\Networking\Database\Models\NetworkProject;
use LaravelNeuro\Networking\Database\Models\NetworkHistory;
use LaravelNeuro\Networking\Database\Models\NetworkState;
use LaravelNeuro\Networking\Database\Models\NetworkAgent;
use LaravelNeuro\Networking\Database\Models\NetworkUnit;
use LaravelNeuro\Contracts\Networking\CorporatePrompt;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Enums\UnitReceiver;
use LaravelNeuro\Enums\TuringMode;
use LaravelNeuro\Enums\TuringHistory;

/**
 * Represents a single transition step within a LaravelNeuro Corporation's state machine.
 * 
 * A Transition encapsulates the logic for processing input from a TuringHead 
 * (the state machine tape), invoking the appropriate agent, and handling prompt 
 * pre-/post-processing as well as output validation. It ties together the current project, 
 * corporation, unit, agent, and models, and records history of execution.
 *
 * @package LaravelNeuro
 */
class Transition {

    use Tracable;

    /**
     * The current project associated with this transition.
     *
     * @var \LaravelNeuro\Networking\Database\Models\NetworkProject
     */
    protected NetworkProject $project;

    /**
     * The corporation to which the project belongs.
     *
     * @var \LaravelNeuro\Networking\Database\Models\NetworkCorporation
     */
    protected NetworkCorporation $corporation;

    /**
     * The active state from the network state machine.
     *
     * @var \LaravelNeuro\Networking\Database\Models\NetworkState
     */
    protected NetworkState $state;

    /**
     * The TuringHead representing the state machine's tape (including the current head).
     *
     * @var TuringHead
     */
    protected TuringHead $head;

    /**
     * The unit associated with this transition.
     *
     * @var \LaravelNeuro\Networking\Database\Models\NetworkUnit
     */
    protected NetworkUnit $unit;

    /**
     * The agent handling this transition, stored as a stdClass object containing agent details.
     *
     * @var \stdClass
     */
    protected \stdClass $agent;

    /**
     * A collection of additional model configurations used during the transition.
     *
     * @var Collection
     */
    protected Collection $models;

    /**
     * Transition constructor.
     *
     * Initializes the Transition instance using a project ID, the TuringHead (state machine tape),
     * and a collection of models. Loads the associated project and corporation, and verifies that there is an active state.
     *
     * @param int $projectId The ID of the project.
     * @param TuringHead $head The TuringHead instance representing the state tape.
     * @param Collection $models A collection of model configurations.
     * @throws \Exception if the project does not exist or if no active state is found.
     */
    public function __construct(int $projectId, TuringHead $head, Collection $models, bool $debug=false, bool $saveHistory=true)
    {
        $this->head = $head;
        $this->models = $models;
        $this->debug = $debug;
        $this->saveHistory = $saveHistory;
        $project = NetworkProject::where('id', $projectId);
        
        if ($project->count() == 1) {
            $this->project = $project->first();
            $this->corporation = NetworkCorporation::with([
                'units.agents', 
                'units.dataSetTemplates.dataSets'
            ])->where('id', $this->project->corporation_id)->first();
            $state = NetworkState::where('project_id', $this->project->id)
                                  ->where('active', true);
            if ($state->count() == 0) {
                throw new \Exception("There is no active state set for the selected corporation. Make sure you initialize this Transition instance with a valid project ID and have an active step set in your corporation's state machine.");
            }
            $this->state = $state->first();
        } else {
            throw new \Exception("Call to non-existent project with the id '$projectId'.");
        }
    }

    /**
     * Preprocesses the prompt before it is used in the transition.
     *
     * This method can be overridden to apply custom transformations to the prompt.
     *
     * @param mixed $prompt The prompt data.
     * @return mixed The preprocessed prompt.
     */
    protected function preProcessPrompt($prompt)
    {
        return $prompt;
    }

    /**
     * Preprocesses input data from the head.
     *
     * This method can be overridden to modify the head's data before processing.
     *
     * @param string $data The raw input data.
     * @return string The preprocessed input data.
     */
    protected function preProcessInput(string $data) : string
    {
        return $data;
    }

    /**
     * Postprocesses the prompt after initial processing.
     *
     * This method can be overridden to apply custom transformations to the prompt after processing.
     *
     * @param mixed $prompt The prompt data.
     * @return mixed The postprocessed prompt.
     */
    protected function postProcessPrompt($prompt)
    {
        return $prompt;
    }

    /**
     * Postprocesses the output data from the head.
     *
     * This method can be overridden to modify the output data after the agent's execution.
     *
     * @param string $data The raw output data.
     * @return string The postprocessed output data.
     */
    protected function postProcessOutput(string $data) : string
    {
        return $data;
    }

    /**
     * Allows for modifications to the pipeline before it is executed.
     *
     * This method can be overridden to apply custom changes to the pipeline instance.
     *
     * @param Pipeline $pipeline The pipeline to modify.
     * @return Pipeline The modified pipeline.
     */
    protected function modifyPipeline(Pipeline $pipeline) : Pipeline
    {
        return $pipeline;
    }

    /**
     * Applies dynamic data to a mutable string.
     *
     * This method searches for placeholders in the mutable string (using a {{type:...}} format)
     * and replaces them with corresponding data extracted from the corporation, project, or head.
     * Supported types include "FromDataSet", "Corporation", and "Head".
     *
     * @param string $mutable The string containing placeholders.
     * @return string The string with placeholders replaced by actual data.
     */
    public function applyData(string $mutable) : string
    {
        $activeUnit = $this->agent->unitName;
        $corporation = $this->corporation;
        $project = $this->project;
        $getValueFromDotNotationParts = function ($data, array $parts) {
            foreach ($parts as $part) {
                // Check if the part has an array index e.g., 'stories[0]'
                if (preg_match('/(.*)\[(\d+)\]/', $part, $matches)) {
                    $key = $matches[1];
                    $index = $matches[2];
            
                    // Access the array element
                    if (is_object($data) && isset($data->$key)) {
                        $data = $data->$key;
                    } elseif (is_array($data) && isset($data[$key])) {
                        $data = $data[$key];
                    } else {
                        return null; // Key not found
                    }
            
                    if (isset($data[$index])) {
                        $data = $data[$index];
                    } else {
                        return null; // Index not found
                    }
                } else {
                    // Access the object property or array element
                    if (is_object($data) && isset($data->$part)) {
                        $data = $data->$part;
                    } elseif (is_array($data) && isset($data[$part])) {
                        $data = $data[$part];
                    } else {
                        return null; // Part not found
                    }
                }
            }
            return $data;
        };

        $mutable = preg_replace_callback('/{{(.*?:.*?)}}/', function($entity) use($corporation, $project, $getValueFromDotNotationParts, $activeUnit) {
            $detect = explode(':', $entity[1]);
            $type = $detect[0];
            switch($type)
            {
                case 'FromDataSet':
                    $loader = str_replace('internal', $activeUnit, $detect[1]);
                    $loader = explode('.', $loader);
                    $data = json_decode($corporation->units->where('name', array_shift($loader))
                        ->first()->dataSetTemplates->where('name', array_shift($loader))
                        ->first()->dataSets->where('project_id', $project->id)
                        ->first()->data);
                    if(count($loader) > 0)
                    {
                        $result = $getValueFromDotNotationParts($data, $loader);
                        $mutate = is_array($result) || is_object($result) ? json_encode($result, JSON_PRETTY_PRINT) : (string)$result;
                    }
                    else
                    {
                        $mutate = json_encode($data, JSON_PRETTY_PRINT);
                    }
                    break;
                case 'Corporation':
                    $loader = $detect[1];
                    $data = $corporation->$loader;
                    $mutate = $data;
                    break;
                case 'Head':
                    if($detect[1] == "data")
                        $mutate = $this->head->getData();
                    else
                        $mutate = $entity[0];
                    break;
                default:
                    $mutate = $entity[0];
                    break;
            }
            return $mutate;
        }, $mutable);
        return $mutable;
    }

    /**
     * Calls the agent to process the current transition.
     *
     * Preprocesses input data from the head and prompt data from the agent,
     * applies dynamic data replacement, decodes the prompt using the agent's prompt class,
     * records prompt history, and then invokes the agent's pipeline to get a response.
     * Also handles output validation and updates the TuringHead's data and mode accordingly.
     *
     * @return void
     * @throws \Exception if errors occur during preprocessing, postprocessing, or agent execution.
     */
    public function callAgent()
    {
        $agent = $this->agent;
        $agent->pipeline = $this->modifyPipeline($agent->pipeline);

        try {
            $data = $this->preProcessInput($this->head->getData());
            $this->head->setData($data);
        } catch(\Exception $e) {
            throw new \Exception("There appears to be a problem with your head preprocessing during one of your Transitions:\n".$e);
        }

        $decodedPrompt = [];
        if (!empty($agent->prompt)) {
            try {
                $prompt = $this->preProcessPrompt($agent->prompt);
            } catch(\Exception $e) {
                throw new \Exception("There appears to be a problem with your prompt preprocessing during one of your Transitions:\n".$e);
            }
            $agent->prompt = $this->applyData($agent->prompt);
            $decodedPrompt["prompt"] = $agent->prompt;
        } else {
            $agent->prompt = $this->applyData($this->head->getData());
            $decodedPrompt["prompt"] = $agent->prompt;
        }
        
        if (!empty($agent->role)) {
            $agent->role = $this->applyData($agent->role);
            $decodedPrompt["role"] = $agent->role;
        }

        if ($agent->outputModel != false) {
            $outputModel = str_replace('internal', $agent->unitName, $agent->outputModel);
            $outputModel = explode('.', $outputModel);
            $validationTemplate = $this->corporation
                ->units->where('name', $outputModel[0])
                ->first()
                ->dataSetTemplates->where('name', $outputModel[1])
                ->first();
            $decodedPrompt["completion"] = [];
            $decodedPrompt["completion"][] = $validationTemplate->completionPrompt;
            $decodedPrompt["completion"][] = $validationTemplate->completionResponse;
        }

        $encodedPrompt = json_encode($decodedPrompt);

        try {
            $prompt = $agent->promptClass::promptDecode($encodedPrompt);
            $prompt = $this->postProcessPrompt($prompt);
        } catch(\Exception $e) {
            throw new \Exception("There appears to be a problem with your prompt/head post-processing during one of your Transitions:\n".$e);
        }
        $this->history(TuringHistory::PROMPT, $prompt->promptEncode());
        $this->debug('New history entry ('.TuringHistory::PROMPT->value.'): ' . $prompt->promptEncode());

        $agent->pipeline->setPrompt($prompt); 

        try {
            $data = $agent->pipeline->output();
            if (is_array($data)) {
                $data = json_encode($data, JSON_PRETTY_PRINT);
            }
            $validated = true;
                
            if ($this->agent->validateOutput) {
                $extractJson = function (string $text) {
                    $startPos = strpos($text, '{');
                    $endPos = strrpos($text, '}');
                    if ($startPos === false || $endPos === false) {
                        return false;
                    }
                    $jsonString = substr($text, $startPos, $endPos - $startPos + 1);
                    $jsonObject = json_decode($jsonString, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return json_encode($jsonObject);
                    } else {
                        return false;
                    }
                };
                $data = $extractJson($data);
            }

            if ($agent->outputModel != false && $this->agent->validateOutput) {
                $compareStructures = function(array $arr1, array $arr2) use (&$compareStructures) {
                    if (!is_array($arr1) || !is_array($arr2)) {
                        return false;
                    }
                    ksort($arr1);
                    ksort($arr2);
                    if (count($arr1) != count($arr2)) {
                        return false;
                    }
                    foreach ($arr1 as $key => $value) {
                        if (!array_key_exists($key, $arr2)) {
                            return false;
                        }
                        if (gettype($value) !== gettype($arr2[$key])) {
                            return false;
                        }
                        if (is_array($value) && !$compareStructures($value, $arr2[$key])) {
                            return false;
                        }
                    }
                    return true;
                };
                        
                $validated = $compareStructures(
                    json_decode($validationTemplate->completionResponse, true), 
                    json_decode($data, true)
                );
            }

            if ($validated) 
            {
                $this->head->setData($data);
                    
                $this->history(TuringHistory::RESPONSE, $this->head->getData());
                $this->debug('New history entry ('.TuringHistory::RESPONSE->value.'): ' . $this->head->getData());
                
                if ($agent->outputModel != false) 
                {
                    $dataSet = $validationTemplate->dataSets->where('project_id', $this->project->id)->first();
                    $dataSet->data = $this->head->getData();
                    $dataSet->save();
                }
            } else {
                
                $this->history(TuringHistory::ERROR, "OutputModel validation error. Setting head mode to TuringMode::STUCK!\n\nTemplate: " . $validationTemplate->completionResponse . "\nResponse: " . $data . "\n\n");
                $this->debug('New history entry ('.TuringHistory::ERROR->value.'): ' . "OutputModel validation error. Setting head mode to TuringMode::STUCK!\n\nTemplate: " . $validationTemplate->completionResponse . "\nResponse: " . $data . "\n\n");
                
                $this->head->setMode(TuringMode::STUCK);
            }       
        } catch (\Exception $e) {
            $this->history(TuringHistory::ERROR, $e);
            $this->debug('New history entry ('.TuringHistory::ERROR->value.'): ' . $e);
            $this->head->setMode(TuringMode::STUCK);
        }
    }

    /**
     * Handles the transition process.
     *
     * Determines the appropriate agent (if not already set), applies the transition by invoking callAgent(),
     * post-processes the output from the TuringHead, updates the head's data, and returns the updated head.
     *
     * @return TuringHead The updated TuringHead after processing the transition.
     * @throws \Exception if no valid agent is found or if errors occur during processing.
     */
    public function handle() : TuringHead
    {
        if (!isset($this->agent)) {
            if (isset($this->unit) && $this->unit->defaultReceiverType == UnitReceiver::AGENT) {
                $defaultAgent = $this->unit->agents->where('name', $this->unit->defaultReceiver)->first();
                if ($defaultAgent) {
                    $this->agent = (new Agent())->init($defaultAgent->id);
                    $this->callAgent();
                }
            }
        } else {
            $this->setUnitById($this->agent->unit_id);
            if (isset($this->agent)) {
                $this->callAgent();
            }           
        }
        $data = $this->postProcessOutput($this->head->getData());
        $this->head->setData($data);
        return $this->head;
    }

    /**
     * Sets the unit for the transition based on the given unit ID.
     *
     * Retrieves the corporation's units and finds the one with the specified ID.
     * Also initializes the default agent for that unit if not already set.
     *
     * @param int $id The unit ID.
     * @return self Returns the current Transition instance.
     * @throws \Exception If the unit is not found.
     */
    public function setUnitById(int $id) : Transition
    {
        $corporation = NetworkCorporation::with('units.agents')->find($this->project->corporation_id);
        if (!$corporation) {
            throw new \Exception("Your project's corporation could not be found in the database. Something is very wrong with your setup.");
        }
        $unit = $corporation->units->find($id);
        if ($unit) {
            $this->unit = $unit;
            if (!isset($this->agent)) {
                if ($this->unit->defaultReceiverType == UnitReceiver::AGENT) {
                    $defaultAgent = $this->unit->agents->where('name', $this->unit->defaultReceiver)->first();
                    if ($defaultAgent) {
                        $this->agent = (new Agent())->init($defaultAgent->id);
                    }
                }
            }
            return $this;
        } else {
            throw new \Exception("Unit with the id '$id' not found in your project's Corporation.");
        }
    }

    /**
     * Sets the unit for the transition based on the unit name.
     *
     * Searches for a unit with the specified name in the corporation.
     *
     * @param string $name The unit name.
     * @return self Returns the current Transition instance.
     * @throws \Exception If the unit is not found.
     */
    public function setUnitByName(string $name) : Transition
    {
        $corporation = NetworkCorporation::with('units.agents')->find($this->project->corporation_id);
        if (!$corporation) {
            throw new \Exception("Your project's corporation could not be found in the database. Something is very wrong with your setup.");
        }
        $unit = $corporation->units->where('name', $name)->first();
        if ($unit) {
            $this->unit = $unit;
            return $this;
        } else {
            throw new \Exception("Unit '$name' not found in your project's Corporation.");
        }
    }

    /**
     * Sets the agent for the transition based on the agent ID.
     *
     * Searches across all units in the corporation for an agent with the specified ID,
     * and initializes that agent.
     *
     * @param int $id The agent ID.
     * @return self Returns the current Transition instance.
     * @throws \Exception If the agent is not found.
     */
    public function setAgentById(int $id) : Transition
    {
        $corporation = NetworkCorporation::with('units.agents')->find($this->project->corporation_id);
        if (!$corporation) {
            throw new \Exception("Your project's corporation could not be found in the database. Something is very wrong with your setup.");
        }
        $agent = false;
        foreach ($corporation->units as $unit) {
            $agent = $unit->agents->where('id', $id)->first();
            if ($agent) break;
        }
        if ($agent) {
            $this->agent = (new Agent())->init($agent->id);
            return $this;
        } else {
            throw new \Exception("Agent with the id '$id' not found in your project's Corporation.");
        }
    }

    /**
     * Sets the agent for the transition based on the agent name.
     *
     * Searches across all units in the corporation for an agent with the specified name,
     * and initializes that agent.
     *
     * @param string $name The agent's name.
     * @return self Returns the current Transition instance.
     * @throws \Exception If the agent is not found.
     */
    public function setAgentByName(string $name) : Transition
    {
        $corporation = NetworkCorporation::with('units.agents')->find($this->project->corporation_id);
        if (!$corporation) {
            throw new \Exception("Your project's corporation could not be found in the database. Something is very wrong with your setup.");
        }
        $agent = false;
        foreach ($corporation->units as $unit) {
            $agent = $unit->agents->where('name', $name)->first();
            if ($agent) break;
        }
        if ($agent) {
            $this->agent = (new Agent())->init($agent->id);
            return $this;
        } else {
            throw new \Exception("Unit '$name' not found in your project's Corporation.");
        }
    }
}