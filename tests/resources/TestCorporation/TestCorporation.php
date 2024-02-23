<?php
namespace App\Corporations\TestCorporation;

use Illuminate\Support\Collection;

use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkCorporation;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkProject;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkHistory;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkState;
use LaravelNeuro\LaravelNeuro\Networking\Corporation;
use LaravelNeuro\LaravelNeuro\Networking\TuringStrip;

use LaravelNeuro\LaravelNeuro\Enums\StuckHandler;
use LaravelNeuro\LaravelNeuro\Enums\TuringState;
use LaravelNeuro\LaravelNeuro\Enums\TuringMove;
use LaravelNeuro\LaravelNeuro\Enums\TuringMode;
use LaravelNeuro\LaravelNeuro\Enums\TuringHistory;

use App\Corporations\TestCorporation\Config;
use App\Corporations\TestCorporation\Bootstrap;

use App\Corporations\TestCorporation\Transitions\ChatCompletionTest;
use App\Corporations\TestCorporation\Transitions\ImageGenerationTest;
use App\Corporations\TestCorporation\Transitions\AudioTTStest;
use LaravelNeuro\LaravelNeuro\Networking\Transition;

/**
* When creating an instance of TestCorporation, be sure to pass the $task parameter to its constructor: 
* __construct(string $task)
*
* Example: new TestCorporation("Perform the task defined in this string");
*
* Using the run() method on an instance of TestCorporation creates the Corporation runtime, during which a task is forwared to the state machine, evaluated and processed at every step, and, finally, consolidated into an output on the final state, after which the state machine shuts down and that output is passed to the loaded NetworkProject instance. This instance is saved to the database and then returned by the run() method.  
*
* Example: $myProject = new TestCorporation("Perform the task defined in this string");
*          $myProject->run;
*
*   At this point, $myProject contains an Eloquent Collection entry from the NetworkProject Model, representing the completed project. The resolution field contains the output data, and you can retrieve the following data from $myProject:
*       int $myProject->id
*       timestamp $myProject->created_at
*       timestamp $myProject->updated_at
*       int $myProject->corporation_id
*       string $myProject->task
*       string $myProject->resolution
*/
class TestCorporation extends Corporation {

    /**
     * The Corporation's namespace is required for various bootstrapping operations, such as loading local Models.
     *
     * @var string or bool
     */
    public $corporationNameSpace = 'TestCorporation';

    /**
     * The number of intermediary steps to set up for your state machine. 
     * Your installation will fill this out based on the number of Transitions you have created, 
     * but you can create more Transitions and increment this number later.
     *
     * @var int
     */
    public int $states = Config::STATES;

    /**
     * The database id of this Corporation, used to instantiate it.
     *
     * @var int
     */
    public int $corporationId = Config::CORPORATION;

    /**
     * This array contains Eloquent Model instances of each Model in this Corporation's Database/Models folder, loaded in by the Bootstrap class.
     *
     * @var Collection
     */
    public Collection $models;

    /**
     * Can be set to REPEAT, CONTINUE, and TERMINATE
     * This setting determines the state machine's behavior when a Transition returns a TuringStrip with a TuringMode mode of STUCK  
     *
     * @var StuckHandler
     */
    public StuckHandler $stuckSetting = StuckHandler::REPEAT;

    protected function initial(TuringStrip $head) : TuringStrip
    {
        NetworkHistory::create([
            'project_id' => $this->project->id, 
            'entryType' => TuringHistory::PROMPT,
            'content' => $this->task
            ]);
        
        $this->models["TestModel"]->save();

        $transition = new ChatCompletionTest($this->project->id, $head, $this->models);

        return $transition->handle();
    }

    protected function continue(TuringStrip $head) : TuringStrip
    {
        
        switch($this->getHeadPosition()) {
			case 1:
				$transition = new AudioTTStest($this->project->id, $head, $this->models);
				break;
			default:
				$transition = new Transition($this->project->id, $head, $this->models);
				break;
		}

        return $transition->handle();
    }

    protected function final(TuringStrip $head) : TuringStrip
    {
        $transition = new ImageGenerationTest($this->project->id, $head, $this->models);
        return $transition->handle();
    }

}