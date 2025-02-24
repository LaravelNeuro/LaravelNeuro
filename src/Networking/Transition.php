<?php
namespace LaravelNeuro\Networking;

use Illuminate\Support\Collection;

use LaravelNeuro\Networking\TuringStrip;
use LaravelNeuro\Networking\Agent;

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

class Transition {

    protected NetworkProject $project;
    protected NetworkCorporation $corporation;
    protected NetworkState $state;
    protected TuringStrip $head;
    protected NetworkUnit $unit;
    protected \stdClass $agent;
    protected Collection $models;

    public function __construct(int $projectId, TuringStrip $head, Collection $models)
    {
        $this->head = $head;
        $this->models = $models;
        $project = NetworkProject::where('id', $projectId);      
        
        if($project->count() == 1)
        {
            $this->project = $project->first();
            $this->corporation = NetworkCorporation::with(['units.agents', 'units.dataSetTemplates.dataSets'])->where('id', $this->project->corporation_id)->first();
            $state  = NetworkState::where('project_id', $this->project->id)
                                  ->where('active', true);
            if ($state->count() == 0)
            {
                throw new \Exception("There is no active state set for the selected corporation. Make sure you initialize this Transition instance with a valid project ID and have an active step set in your corporation's state machine.");
            }

            $this->state = $state->first();
        }
        else
        {
            throw new \Exception("Call to non-existent project with the id '$projectId'.");
        }

    }

    protected function preProcessPrompt($prompt)
    {
        return $prompt;
    }

    protected function preProcessInput(string $data) : string
    {
        return $data;
    }

    protected function postProcessPrompt($prompt)
    {
        return $prompt;
    }

    protected function postProcessOutput(string $data) : string
    {
        return $data;
    }

    protected function modifyPipeline(Pipeline $pipeline) : Pipeline
    {
        return $pipeline;
    }

    public function applyData(string $mutable) : string
    {
        $activeUnit = $this->agent->unitName;
        $corporation = $this->corporation;
        $project = $this->project;
        $getValueFromDotNotationParts = 
            function ($data, array $parts) 
            {
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

        $mutable = preg_replace_callback('/{{(.*?:.*?)}}/', function($entity) use($corporation, $project, $getValueFromDotNotationParts, $activeUnit){
      
            $detect = explode(':', $entity[1]);
            $type = $detect[0];

            switch($type)
            {
                case 'FromDataSet':
                    $loader = str_replace('internal', $activeUnit, $detect[1]);
                    $loader = explode('.', $loader);
                    $data = json_decode($corporation->units->where('name', array_shift($loader))->first()->dataSetTemplates->where('name', array_shift($loader))->first()->dataSets->where('project_id', $project->id)->first()->data);
                        if(count($loader) > 0)
                        {
                            $mutate = $getValueFromDotNotationParts($data, $loader);
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

    public function callAgent()
    {
        $agent = $this->agent;

        $agent->pipeline = $this->modifyPipeline($agent->pipeline);

        try {
            $data = $this->preProcessInput($this->head->getData());
            
            $this->head->setData($data);
            }
            catch(\Exception $e)
            {
                throw new \Exception("There appears to be a problem with your head preprocessing during one of your Transitions:\n".$e);
            }

        $decodedPrompt = [];

        if(!empty($agent->prompt)) 
        {
            try {
                $prompt = $this->preProcessPrompt($agent->prompt);
                }
                catch(\Exception $e)
                {
                    throw new \Exception("There appears to be a problem with your prompt preprocessing during one of your Transitions:\n".$e);
                }
            $agent->prompt = $this->applyData($agent->prompt);
            $decodedPrompt["prompt"] = $agent->prompt;
        }
        else
        {
            $agent->prompt = $this->applyData($this->head->getData());
            $decodedPrompt["prompt"] = $agent->prompt;
        }
        
        if(!empty($agent->role))
            {
                $agent->role = $this->applyData($agent->role);
                $decodedPrompt["role"] = $agent->role;
            }

        if($agent->outputModel != false)
            {
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
            if(!($agent->promptClass instanceof CorporatePrompt)) throw new \Exception("Your transition does not return a prompt with the CorporatePrompt. While pipelines do not necessarily need to implement this interface, Transitions do, so it is recommended to extend BasicPrompt, which implements CorporatePrompt, or use the interface on your custom prompt.");
            
            $prompt = $agent->promptClass::promptDecode($encodedPrompt);

            $prompt = $this->postProcessPrompt($prompt);
            }
            catch(\Exception $e)
            {
                throw new \Exception("There appears to be a problem with your prompt/head post-processing during one of your Transitions:\n".$e);
            }

        NetworkHistory::create([
            'project_id' => $this->project->id, 
            'agent_id' => $this->agent->id,
            'entryType' => TuringHistory::PROMPT,
            'content' => $prompt->promptEncode()
            ]); 

        $agent->pipeline->setPrompt(
            $prompt
            ); 

        try
        {
            $data = $agent->pipeline->output();
            if(is_array($data)) $data = json_encode($data, JSON_PRETTY_PRINT);

            $validated = true;
                
                if($this->agent->validateOutput)
                {
                    $extractJson = function (string $text) {
                        // Find the position of the first opening brace and the last closing brace
                        $startPos = strpos($text, '{');
                            $endPos = strrpos($text, '}');
        
                            // Check if both braces are found
                            if ($startPos === false || $endPos === false) {
                                return false; // Not a valid JSON string
                            }
        
                            // Extract the JSON string
                            $jsonString = substr($text, $startPos, $endPos - $startPos + 1);
        
                            // Decode the JSON string
                            $jsonObject = json_decode($jsonString, true);
        
                            // Check if decoding was successful
                            if (json_last_error() === JSON_ERROR_NONE) {
                                return json_encode($jsonObject);
                            } else {
                                return false; // JSON decoding error
                            }
                    };
                    $data = $extractJson($data);
                }

                if($agent->outputModel != false && $this->agent->validateOutput)
                    {
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
                        
                        $validated = $compareStructures(json_decode($validationTemplate->completionResponse, true), json_decode($data, true));
                    }

                if($validated)
                {
                    $this->head->setData($data);
                    
                    NetworkHistory::create([
                        'project_id' => $this->project->id, 
                        'agent_id' => $this->agent->id,
                        'entryType' => TuringHistory::RESPONSE,
                        'content' => $this->head->getData()
                        ]);
        
                    if($agent->outputModel != false)
                    {
                        $dataSet = $validationTemplate->dataSets->where('project_id', $this->project->id)->first();
                        $dataSet->data = $this->head->getData();
                        $dataSet->save();
                    }
                }
                else 
                {
                    NetworkHistory::create([
                        'project_id' => $this->project->id, 
                        'agent_id' => $this->agent->id,
                        'entryType' => TuringHistory::ERROR,
                        'content' => "OutputModel validation error. Setting head mode to TuringMode::STUCK!\n\n
                        Template: ".$validationTemplate->completionResponse."\n
                        Response: ".$data."\n\n
                        "
                        ]);

                    $this->head->setMode(TuringMode::STUCK);
                }       
        }
        catch (\Exception $e)
        {
            NetworkHistory::create([
                'project_id' => $this->project->id, 
                'agent_id' => $this->agent->id,
                'entryType' => TuringHistory::ERROR,
                'content' => $e
                ]);
            $this->head->setMode(TuringMode::STUCK);
        }
    }

    public function handle() : TuringStrip
    {
        if(!isset($this->agent))
        {
            if(isset($this->unit) && $this->unit->defaultReceiverType == UnitReceiver::AGENT)
            {
                $defaultAgent = $this->unit->agents->where('name', $this->unit->defaultReceiver)->first();
                if($defaultAgent) {
                    $this->agent = (new Agent())->init($defaultAgent->id);
                    $this->callAgent();
                }
            }
        }
        else
        {
            $this->setUnitById($this->agent->unit_id);
            if(isset($this->agent)) $this->callAgent();           
        }

        $data = $this->postProcessOutput($this->head->getData());
        $this->head->setData($data);

        return $this->head;
    }

    public function setUnitById(int $id) : Transition
    {
        $corporation = NetworkCorporation::with('units.agents')->find($this->project->corporation_id);

        if(!$corporation) {
            throw new \Exception("Your project's corporation could not be found in the database. Something is very wrong with your setup.");
        }

        $unit = $corporation->units->find($id);

        if($unit)
        {
            $this->unit = $unit;
            if(!isset($this->agent))
            {
                if($this->unit->defaultReceiverType == UnitReceiver::AGENT)
                {
                    $defaultAgent = $this->unit->agents->where('name', $this->unit->defaultReceiver)->first();
                    if($defaultAgent) {
                        $this->agent = (new Agent())->init($defaultAgent->id);
                    }
                }
            }

            return $this;
        }
        else
        {
            throw new \Exception("Unit with the id '$id' not found in your project's Corporation.");
        }

    }

    public function setUnitByName(string $name) : Transition
    {
        $corporation = NetworkCorporation::with('units.agents')->find($this->project->corporation_id);

        if(!$corporation) {
            throw new \Exception("Your project's corporation could not be found in the database. Something is very wrong with your setup.");
        }

        $unit = $corporation->units->where('name', $name)->first();

        if($unit)
        {
            $this->unit = $unit;

            return $this;
        }
        else
        {
            throw new \Exception("Unit '$name' not found in your project's Corporation.");
        }
        
    }

    public function setAgentById(int $id) : Transition
    {
        $corporation = NetworkCorporation::with('units.agents')->find($this->project->corporation_id);

        if(!$corporation) {
            throw new \Exception("Your project's corporation could not be found in the database. Something is very wrong with your setup.");
        }

        $agent = false;

        foreach($corporation->units as $unit)
        {
            $agent = $unit->agents->where('id', $id)->first();
            if($agent) break;
        }

        if($agent)
        {
            $this->agent = (new Agent())->init($agent->id);

            return $this;
        }
        else
        {
            throw new \Exception("Agent with the id '$id' not found in your project's Corporation.");
        }

    }

    public function setAgentByName(string $name) : Transition
    {
        $corporation = NetworkCorporation::with('units.agents')->find($this->project->corporation_id);

        if(!$corporation) {
            throw new \Exception("Your project's corporation could not be found in the database. Something is very wrong with your setup.");
        }

        $agent = false;

        foreach($corporation->units as $unit)
        {
            $agent = $unit->agents->where('name', $name)->first();
            if($agent) break;
        }

        if($agent)
        {
            $this->agent = (new Agent())->init($agent->id);

            return $this;
        }
        else
        {
            throw new \Exception("Unit '$name' not found in your project's Corporation.");
        }
        
    }

}