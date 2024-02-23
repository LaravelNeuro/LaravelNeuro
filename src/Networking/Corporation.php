<?php
namespace LaravelNeuro\LaravelNeuro\Networking;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkCorporation;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkDataSet;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkHistory;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkProject;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkState;

use LaravelNeuro\LaravelNeuro\Networking\TuringStrip;

use LaravelNeuro\LaravelNeuro\Enums\TuringMove;
use LaravelNeuro\LaravelNeuro\Enums\TuringMode;
use LaravelNeuro\LaravelNeuro\Enums\TuringState;
use LaravelNeuro\LaravelNeuro\Enums\TuringHistory;
use LaravelNeuro\LaravelNeuro\Enums\StuckHandler;

class Corporation {

    private TuringStrip $head;
    public NetworkProject $project;
    public $corporationNameSpace = false;
    
    public NetworkCorporation $corporation;
    public NetworkState $stateMachine;
    public Collection $units;
    public Collection $models;
    public int $corporationId;
    public int $states = 0;
    public int $history;
    public string $task;
    public Collection $stateMap;
    public StuckHandler $stuckSetting = StuckHandler::REPEAT;
    public bool $debug = false;
    
    public function __construct(string $task, bool $debug = false, bool $integrityCheck = false, array $new = ["name" => "DummyCorp", "description" => "DummyDesc"])
    {
        if ($integrityCheck) return;
        $this->debug = $debug;

        $this->debug("Initiating Corporation.");

        $this->task = $task;
        $this->head = new TuringStrip;
        $this->head->setData($this->task);

        if($this->corporationNameSpace !== false)
        {
            $nameSpace = $this->corporationNameSpace;

            if(!Str::contains($nameSpace, '\\'))
            {
                $this->corporationNameSpace = 'App'.'\\'.config('laravelneuro.default_namespace', 'Corporations').'\\'.$nameSpace;
            }

            $bootstrap = $this->corporationNameSpace.'\\Bootstrap';
            $this->models = $bootstrap::models();
        }

        if(empty($this->corporationId))
        {
            $this->corporation = new NetworkCorporation;
            $this->corporation->name = $new["name"];
            $this->corporation->description = $new["description"];
            $this->corporation->save();
            $this->corporation = NetworkCorporation::with(['units.agents', 'units.dataSetTemplates'])->where('id', $this->corporation->id)->first();
        }
        else
        {
            $this->corporation = NetworkCorporation::with(['units.agents', 'units.dataSetTemplates'])->where('id', $this->corporationId)->first();
        }

        $this->units = $this->corporation->units;

        $this->project = new NetworkProject;
        $this->project->corporation_id = $this->corporation->id;
        $this->project->task = $this->task;
        $this->project->save();

        NetworkState::where('project_id', $this->project->id)->delete();
        NetworkDataSet::where('project_id', $this->project->id)->delete();

        foreach($this->units as $unit)
        {
            $templates = $unit->dataSetTemplates;
            
            foreach($templates as $template)
            {
            $emptySet = $this->scrubDataSet(json_decode($template->completionResponse));
            NetworkDataSet::create([
                'template_id' => $template->id, 
                'project_id' => $this->project->id, 
                'data' => json_encode($emptySet, JSON_PRETTY_PRINT)]);
            }    
        }

        $this->debug("Setting up INITIAL state.");
        NetworkState::create([
            'type' => TuringState::INITIAL, 
            'active' => true, 
            'project_id' => $this->project->id, 
            'data' => $this->task
            ]);
            
            $this->debug("Setting up INTERMEDIARY states. Count: ". ($this->states > 2 ? ($this->states - 2) : 0));

            for($i = ($this->states - 1); $i > 0; $i--)
            {
                NetworkState::create([
                    'type' => TuringState::INTERMEDIARY, 
                    'active' => false, 
                    'project_id' => $this->project->id, 
                    ]);
            }

    $this->debug("Setting up FINAL state.");
        NetworkState::create([
            'type' => TuringState::FINAL, 
            'active' => false, 
            'project_id' => $this->project->id, 
            ]);

        $this->stateMap = NetworkState::where('project_id', $this->project->id)->orderBy('id', 'asc')->get();
        
        $this->debug("Corporation Initiated.");

        $history = NetworkHistory::create([
            'entryType' => TuringHistory::OTHER, 
            'project_id' => $this->project->id, 
            'content' => 'Corporation has been initiated successfully.'
            ]);
        
        $this->history = $history->id;
    }

    protected function debug(string $info)
    {
        if($this->debug) $dateObj = \DateTime::createFromFormat('0.u00 U', microtime());
        if($this->debug) echo '['.$dateObj->format('H:i:s.u').']: '. $info . "\n";
    }

    function scrubDataSet($data) {
        if (is_array($data) || is_object($data)) {
            foreach ($data as &$value) {
                $value = $this->scrubDataSet($value);
            }
        } else {
            return gettype($data);
        }
        return $data;
    }

    protected function initial(TuringStrip $head) : TuringStrip
    {
        NetworkHistory::create([
            'project_id' => $this->project->id, 
            'entryType' => TuringHistory::PROMPT,
            'content' => $this->task
            ]);
        
        $transition = new Transition($this->project->id, $head, $this->models);

        return $transition->handle();
    }

    protected function continue(TuringStrip $head) : TuringStrip
    {
        $transition = new Transition($this->project->id, $head, $this->models);

        return $transition->handle();
    }

    protected function final(TuringStrip $head) : TuringStrip
    {
        $transition = new Transition($this->project->id, $head, $this->models);

        return $transition->handle();
    }

    public function getHeadPosition()
    {
        return $this->head->getPosition();
    }

    public function setHeadPosition(int $headPosition)
    {
        if($headPosition > ($this->states + 1)) $headPosition = $this->states + 1;
        elseif($headPosition < 0) $headPosition = 0;
        $this->head->setPosition($headPosition);
    }

    private function goTo($line, NetworkState $active) : NetworkState
    {
        switch($line)
        {
            case TuringMove::NEXT :
                
                if($active->type != TuringState::FINAL)
                {
                    $active->active = false;
                    $active->save();

                    $this->setHeadPosition($this->getHeadPosition() + 1);

                    $this->debug("Moving to next state: ". $this->getHeadPosition() . " (id: ". $this->stateMap->get($this->getHeadPosition())->id . ")");
                    
                    $newState = NetworkState::where('project_id', $this->project->id)
                                ->where('id', $this->stateMap->get($this->getHeadPosition())->id)->first();
                        if($newState == false)
                        {
                            $newState = NetworkState::where('project_id', $this->project->id)
                                    ->where('type', TuringState::FINAL)->first();
                            $this->head->setNext(TuringMove::OUTPUT);
                        }
                    $newState->active = true;
                    $newState->data = $this->head->getData();
                    $newState->save();
                }
                else
                {
                    throw new \Exception("There is an error in your state machine setup. The head just attempted to move past the FINAL step.");
                }

                break;
            case TuringMove::OUTPUT :
                
                $active->active = false;
                $active->save();
                $newState = NetworkState::where('project_id', $this->project->id)
                            ->where('type', 'FINAL')->first();
                $newState->active = true;     
                $newState->data = $this->head->getData();
                $newState->save();

                break;
            case TuringMove::REPEAT :
                
                $active->data = $this->head->getData();
                $newState = $active;
                $newState->save();

                break;
            default:

                $active->active = false;
                $active->save();
                $newState = NetworkState::where('project_id', $this->project->id)
                            ->where('id', $line)->first();
                $newState->active = true;
                $newState->data = $this->head->getData();
                $newState->save();

                break;
        }

        $stateMapElement = $this->stateMap->find($newState->id);
        $this->setHeadPosition($this->stateMap->search(function ($item) use ($stateMapElement) {
                                    return $item->getKey() === $stateMapElement->getKey();
                                }));
        
        return $newState;
    }

    /**
     * The run() method creates the Corporation runtime, during which a task is forwared to the state machine, evaluated and processed at every step, and, finally, consolidated into an output on the final state, after which the state machine shuts down and that output is returned by the function.  
     *
     * @method
     */
    public function run() : string
    {
        $this->debug("Starting state machine.");
        $iterations = 0;
        while(1)
        {       
            
            $iterations++;
            $this->debug("State Machine iteration $iterations.");

            $exit = false;
            $active = NetworkState::where('project_id', $this->project->id)
                                ->where('active', true)->first();
            
            $this->debug("Active state: ".$this->getHeadPosition()." (state id: ".$active->id." )");
              
            switch($active->type ?? false)
                {
                    case TuringState::INITIAL :

                        $this->debug("TuringState: INITIAL");
                        $this->head = $this->initial($this->head);

                        break;
                    case TuringState::INTERMEDIARY :

                        $this->debug("TuringState: INTERMEDIARY");
                        $this->head = $this->continue($this->head);

                        break;
                    case TuringState::FINAL :

                        $this->debug("TuringState: FINAL");
                        $this->head = $this->final($this->head);
                        if(!($this->head->getNext() == TuringMove::REPEAT))
                            {
                                $this->head->setMode(TuringMode::COMPLETE);
                                $active->active = false;
                            }
                        else
                            {
                                $this->head->setMode(TuringMode::CONTINUE);
                            }
                        $active->save();
                        break;
                    default:
                        $exit = true;
                        break;            
                }  

            switch($this->head->getMode())
                {
                    case TuringMode::CONTINUE :
                        $this->debug("TuringMode: CONTINUE");                    
                        $line = $this->head->getNext();
                        $this->goTo($line, $active);
                        break;
                    case TuringMode::STUCK :
                        $this->debug("TuringMode: STUCK");
                        $this->debug("Sleeping for 3 Second to avoid hitting API Rate-Limits.");
                        sleep(3);
                        $this->debug("Stuck Setting: ".$this->stuckSetting->value);
                        switch($this->stuckSetting)
                        {
                            case StuckHandler::REPEAT :
                                $this->debug("Setting TuringMode: CONTINUE and repeating step.");
                                $this->head->setMode(TuringMode::CONTINUE);
                                $this->goTo($active->id, $active);
                                break;
                            case StuckHandler::CONTINUE :
                                $this->debug("Setting TuringMode: CONTINUE and moving to next step.");
                                $this->head->setMode(TuringMode::CONTINUE);
                                $this->goTo(TuringMove::NEXT, $active);
                                break;
                            case StuckHandler::TERMINATE :
                                $this->head->setMode(TuringMode::COMPLETE);
                                $this->goTo(TuringMove::OUTPUT, $active);
                                break; 
                        }
                        break;
                    case TuringMode::COMPLETE :
                        $this->debug("TuringMode: COMPLETE. Shutting down.");
                        $this->goTo(TuringMove::OUTPUT, $active);
                        $exit = true;
                        break;    
                } 

            if($this->debug)
            {
                $newHistoryEntries = NetworkHistory::where('project_id', $this->project->id)
                                                   ->where('id', '>', $this->history);
                if($newHistoryEntries->count() > 0)
                {
                    try{
                    foreach($newHistoryEntries->get() as $entry)
                    {
                        $this->debug('New history entry ('.$entry->entryType->value.'): ' . $entry->content);
                    }
                    $this->history = $newHistoryEntries->last()->id;
                    }
                    catch(\Exception $e)
                    {
                        $this->debug('No new history entries.');
                    }
                }
            }

            if($exit) break; 
            $this->corporation = $this->corporation->fresh(['units.agents', 'units.dataSetTemplates.dataSets']);
        }

        $this->debug("State Machine has terminated. Passing output to Model.");

        $data = NetworkState::where('project_id', $this->project->id)
                            ->where('type', TuringState::FINAL)
                            ->first()
                            ->data;

        $this->debug("Output data: \n" . $data);

        $this->project->resolution = $data;
        $this->project->save();

        return $this->project;

    }

}