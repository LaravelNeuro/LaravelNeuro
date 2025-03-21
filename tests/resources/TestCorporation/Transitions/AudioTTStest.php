<?php
namespace App\Corporations\TestCorporation\Transitions;

use LaravelNeuro\Networking\Database\Models\NetworkCorporation;
use LaravelNeuro\Networking\Database\Models\NetworkProject;
use LaravelNeuro\Networking\TuringHead;
use LaravelNeuro\Networking\Transition;
use LaravelNeuro\Contracts\AiModel\Pipeline;
use LaravelNeuro\Drivers\WebRequest\GuzzleDriver;

use App\Corporations\TestCorporation\Config;

use Illuminate\Support\Collection;

use Tests\Helpers\ApiSimulator;

Class AudioTTStest extends Transition
{
    /**
    * An Eloquent Collection instance of the active project Model.
    * @var NetworkProject
    */
    protected NetworkProject $project;

    /**
    * An Eloquent Collection instance of the active corporation Model.
    * This instance will Eager Load units belonging to this corporation (accessible with the dynamic property 'units').
    * Each unit will also Eager Load agents and dataSetTemplates belonging to it (accessible with the dynamic properties 'agents' and 'dataSetTemplates')
    * Each dataSetTemplate will also Eager Load dataSets belonging to it (accessible with the dynamic property 'dataSets')
    * Example of accessing a dataSet: $this->corporation->units->first()->dataSetTemplates->first()->dataSets->first();
    * You can use Eloquent to traverse the Eager Loaded relations.
    * @var NetworkCorporation
    */
    protected NetworkCorporation $corporation;

    public function __construct(int $projectId, TuringHead $head, Collection $models)
    {
        parent::__construct($projectId, $head, $models);
        
        /**
         * Agent Name: AudioTTS
         * attaches this agent to this Transition instance.
         */
        $this->setAgentById(Config::TRANSITION_AUDIOTTS_AGENT);
    }

    /**
     * Allows the modification of the AI agent's Pipeline to, for example: Change the model or the API link conditionally, or to pass additional headers such as an API key.
     * @method
     */
    protected function modifyPipeline(Pipeline $pipeline) : Pipeline
    {
        $testAudioPath = __DIR__ . '/../resources/testaudio1.mp3';
        $mock = new ApiSimulator;
        $mock->expects(function ($request) {
            $body = (string) $request->getBody();
                    if(json_validate($body))
                    {
                        $requestData = json_decode($body);
                        if($requestData->text == "Dies ist ein TTS-Test."
                        && $requestData->model_id == "eleven_multilingual_v2") 
                            return true;
                        else
                            return false;
                    }
                    else
                        return false;
                })
             ->responds(file_get_contents($testAudioPath));

        $handlerStack = ['handler' => $mock->getHandler()];

        $driver = $pipeline->driver();
            if ($driver instanceof GuzzleDriver) {
                $driver->setClient($handlerStack);
            }
        
        return $pipeline;
    }

    /**
     * Receives a fresh prompt instance, which allows, for example, the addition of chat-completion examples using pushUser() and pushAgent() on $prompt, if it is an SUAprompt object (the default).
     * Once preProcessInput has been called, if $prompt is an SUAprompt, a final pushUser() method will be applied to $prompt to append $this->head->getData() as the active request
     * @method
     */
    protected function preProcessPrompt($prompt)
    {
        return $prompt;
    }

    /**
     * Allows manipulation of $this->head->data before the data is attached to the SUAprompt using pushUser() as well as the implementation of additional logic at this point.
     * @method
     */
    protected function preProcessInput(string $data) : string
    {
        return $data;
    }

    /**
     * Receives the finalized prompt instance before it is passed to the agent pipeline, which allows manipulation of the prompt content.
     * The prompt class depends on the agent settings and should be maintained.
     * @method
     */
    protected function postProcessPrompt($prompt)
    {
        $processedPrompt = $prompt;

        // Implement your post-processor logic here, modifying $processedPrompt and executing other logic as needed.
        // To avoid unpredictable behavior, it is recommended to maintain type integrity between input and outpot, which the check below ensures.

        if (
            gettype($prompt) !== gettype($processedPrompt) &&
            (is_object($prompt) && get_class($prompt) !== get_class($processedPrompt))
            ) {
            throw new \InvalidArgumentException("The type of the processed prompt must match the type of the input prompt.");
        }

        return $processedPrompt;
    }

    /**
     * Allows manipulation of $this->head->data after the prompt has been executed and output written to $this->head->data as well as the implementation of additional logic at this point.
     * @method
     */
    protected function postProcessOutput(string $data) : string
    {
        $this->models->get("TestModel")->audioFile = json_decode($data)->fileName;
        return $data;
    }
}