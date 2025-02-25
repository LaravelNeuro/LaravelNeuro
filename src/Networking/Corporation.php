<?php
namespace LaravelNeuro\Networking;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use LaravelNeuro\Networking\Database\Models\NetworkCorporation;
use LaravelNeuro\Networking\Database\Models\NetworkDataSet;
use LaravelNeuro\Networking\Database\Models\NetworkHistory;
use LaravelNeuro\Networking\Database\Models\NetworkProject;
use LaravelNeuro\Networking\Database\Models\NetworkState;

use LaravelNeuro\Networking\TuringHead;

use LaravelNeuro\Enums\TuringMove;
use LaravelNeuro\Enums\TuringMode;
use LaravelNeuro\Enums\TuringState;
use LaravelNeuro\Enums\TuringHistory;
use LaravelNeuro\Enums\StuckHandler;

/**
 * Class Corporation
 *
 * Manages a Laravel Neuro Corporation's state machine. A Corporation represents a
 * complete AI project execution environment, responsible for initializing the project,
 * creating the initial, intermediary, and final states, and managing the Turing machine's head.
 *
 * The class:
 * - Initializes a TuringHead with the provided task.
 * - Loads or creates a NetworkCorporation record based on configuration.
 * - Creates a new project and wipes any previous state or dataset entries.
 * - Sets up an initial state using the task, followed by intermediary states and a final state.
 * - Maintains a state map and logs history entries.
 *
 * @package LaravelNeuro
 */
class Corporation {

    use Tracable;

    /**
     * The TuringHead instance that acts as the "head" of the state machine.
     *
     * @var TuringHead
     */
    private TuringHead $head;

    /**
     * The active project for this corporation.
     *
     * @var \LaravelNeuro\Networking\Database\Models\NetworkProject
     */
    public NetworkProject $project;

    /**
     * The namespace for the corporation. False if not set.
     *
     * @var string|bool
     */
    public $corporationNameSpace = false;
    
    /**
     * The NetworkCorporation model instance representing the corporation.
     *
     * @var \LaravelNeuro\Networking\Database\Models\NetworkCorporation
     */
    public NetworkCorporation $corporation;

    /**
     * The state machine (NetworkState model) for the corporation.
     *
     * @var \LaravelNeuro\Networking\Database\Models\NetworkState
     */
    public NetworkState $stateMachine;

    /**
     * A collection of units associated with the corporation.
     *
     * @var Collection
     */
    public Collection $units;

    /**
     * A collection of additional model configurations.
     *
     * @var Collection
     */
    public Collection $models;

    /**
     * The corporation ID (if pre-existing).
     *
     * @var int
     */
    public int $corporationId;

    /**
     * The total number of states in the state machine.
     *
     * @var int
     */
    public int $states = 0;

    /**
     * The ID of the initial history entry.
     *
     * @var int
     */
    public int $history;

    /**
     * The task or prompt assigned to this corporation.
     *
     * @var string
     */
    public string $task;

    /**
     * A collection representing the state map (ordered states) for the project.
     *
     * @var Collection
     */
    public Collection $stateMap;

    /**
     * The stuck handler setting, determining how to handle a stuck state.
     *
     * @var \LaravelNeuro\Enums\StuckHandler
     */
    public StuckHandler $stuckSetting = StuckHandler::REPEAT;
    
    /**
     * Corporation constructor.
     *
     * Initializes the corporation with a given task, debug flag, and optional integrity check.
     * Sets up the TuringHead with the task, loads or creates a NetworkCorporation record,
     * initializes a new project, cleans any pre-existing state or dataset entries,
     * creates initial, intermediary, and final states, and logs the initiation in history.
     *
     * @param string $task The task to be processed by the corporation.
     * @param bool $debug Whether debugging output should be enabled.
     * @param bool $integrityCheck If true, performs only an integrity check and returns immediately.
     * @param array $new Default values for creating a new corporation (name and description).
     * @param bool $saveHistory Whether history entries should be saved to the database. Defaults to true.
     */
    public function __construct(string $task, bool $debug = false, bool $integrityCheck = false, array $new = ["name" => "DummyCorp", "description" => "DummyDesc"], bool $saveHistory = true)
    {
        if ($integrityCheck) return;
        $this->debug = $debug;

        $this->debug("Initiating Corporation.");

        $this->task = $task;
        $this->head = new TuringHead;
        $this->head->setData($this->task);
        $this->saveHistory = $saveHistory;

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

        $this->history(TuringHistory::OTHER, 'Corporation has been initiated successfully.');
        $this->debug('New history entry ('.TuringHistory::OTHER->value.'): ' . 'Corporation has been initiated successfully.');
    }

    /**
     * Recursively transforms dataset data by replacing non-array/object values with their types.
     *
     * @param mixed $data The dataset data to scrub.
     * @return mixed The scrubbed dataset.
     */
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

    /**
     * Processes the initial transition.
     *
     * Records the initial prompt in history and creates the first transition.
     *
     * @param TuringHead $head The current TuringHead instance.
     * @return TuringHead The updated head after processing the initial transition.
     */
    protected function initial(TuringHead $head) : TuringHead
    {
        $this->history(TuringHistory::PROMPT, $this->task);
        $this->debug('New history entry ('.TuringHistory::PROMPT->value.'): ' . $this->task);

        $transition = new Transition(projectId: $this->project->id, head: $head, models: $this->models, debug: $this->debug, saveHistory: $this->saveHistory);

        return $transition->handle();
    }

    /**
     * Processes an intermediary transition.
     *
     * Creates a new Transition instance and returns the updated head after processing.
     *
     * @param TuringHead $head The current TuringHead instance.
     * @return TuringHead The updated head.
     */
    protected function continue(TuringHead $head) : TuringHead
    {
        $transition = new Transition(projectId: $this->project->id, head: $head, models: $this->models, debug: $this->debug, saveHistory: $this->saveHistory);

        return $transition->handle();
    }

    /**
     * Processes the final transition.
     *
     * Creates a new Transition instance, processes the final transition, and returns the updated head.
     *
     * @param TuringHead $head The current TuringHead instance.
     * @return TuringHead The updated head.
     */
    protected function final(TuringHead $head) : TuringHead
    {
        $transition = new Transition(projectId: $this->project->id, head: $head, models: $this->models, debug: $this->debug, saveHistory: $this->saveHistory);

        return $transition->handle();
    }

    /**
     * Retrieves the current position of the head.
     *
     * @return int The current head position.
     */
    public function getHeadPosition()
    {
        return $this->head->getPosition();
    }

    /**
     * Sets the head position, ensuring it is within valid bounds.
     *
     * @param int $headPosition The desired head position.
     * @return void
     */
    public function setHeadPosition(int $headPosition)
    {
        if($headPosition > ($this->states + 1)) $headPosition = $this->states + 1;
        elseif($headPosition < 0) $headPosition = 0;
        $this->head->setPosition($headPosition);
    }

    /**
     * Moves the head to a target state based on a directive.
     *
     * Handles different move directives:
     * - TuringMove::NEXT: Move to the next state (unless at FINAL).
     * - TuringMove::OUTPUT: Move to the FINAL state.
     * - TuringMove::REPEAT: Remain on the current state, updating its data.
     * - Otherwise, move to a specific state ID.
     *
     * Updates the state map and head position accordingly.
     *
     * @param mixed $line The move directive or state ID.
     * @param \LaravelNeuro\Networking\Database\Models\NetworkState $active The current active state.
     * @return \LaravelNeuro\Networking\Database\Models\NetworkState The new active state.
     * @throws \Exception if attempting to move past the FINAL state.
     */
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
     * Runs the corporation's state machine.
     *
     * Enters an iterative loop to process state transitions until a termination condition is met.
     * At each iteration, it:
     * - Retrieves the current active state.
     * - Processes the state based on its type (INITIAL, INTERMEDIARY, FINAL).
     * - Applies transition logic based on the head's mode (CONTINUE, STUCK, COMPLETE).
     * - Logs new history entries.
     * - Updates the corporation data.
     *
     * Once the state machine terminates, it records the final output as the project's resolution.
     *
     * @return string The project's resolution output.
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