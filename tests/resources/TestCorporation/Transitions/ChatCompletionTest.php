<?php
namespace App\Corporations\TestCorporation\Transitions;

use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkCorporation;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkProject;
use LaravelNeuro\LaravelNeuro\Networking\Transition;
use LaravelNeuro\LaravelNeuro\Networking\TuringStrip;
use LaravelNeuro\LaravelNeuro\Prompts\SUAprompt;
use LaravelNeuro\LaravelNeuro\Pipeline;

use App\Corporations\TestCorporation\Config;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Constraint\ObjectHasProperty;
use Tests\Helpers\ApiSimulator;

Class ChatCompletionTest extends Transition
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

    public function __construct(int $projectId, TuringStrip $head, Collection $models)
    {
        parent::__construct($projectId, $head, $models);

        /**
         * Unit Name: TestUnitOne
         * attaches this unit to this Transition instance. This is intended for Transitions that should utilize the unit's default receiver.
         */        
        $this->setUnitById(Config::TRANSITION_TESTUNITONE_UNIT);
    }

    /**
     * Allows the modification of the AI agent's Pipeline to, for example: Change the model or the API link conditionally, or to pass additional headers such as an API key.
     * @method
     */
    protected function modifyPipeline(Pipeline $pipeline) : Pipeline
    {
        $mock = new ApiSimulator;
        $mock->expects(function ($request) {
            $body = (string) $request->getBody();
                    if(json_validate($body))
                    {
                        $requestData = json_decode($body);
                        $requestUri = (string) $request->getUri();
                        if($requestUri == 'https://api.openai.com/v1/chat/completions'
                        && property_exists($requestData, "model") 
                        && property_exists($requestData, "messages")
                        && is_array($requestData->messages)
                        && count($requestData->messages) > 0) 
                            return true;
                        else
                            return false;
                    }
                    else
                        return false;
                })
             ->responds('{
                "id": "chatcmpl-123",
                "object": "chat.completion",
                "created": 1677652288,
                "model": "gpt-3.5-turbo-0613",
                "system_fingerprint": "fp_44709d6fcb",
                "choices": [{
                  "index": 0,
                  "message": {
                    "role": "assistant",
                    "content": "{\"testParameter\": \"Dies ist ein TTS-Test.\"}"
                  },
                  "logprobs": null,
                  "finish_reason": "stop"
                }],
                "usage": {
                  "prompt_tokens": 9,
                  "completion_tokens": 12,
                  "total_tokens": 21
                }
              }');

        $handlerStack = ['handler' => $mock->getHandler()];

        $pipeline->setClient($handlerStack);

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
        $this->models->get("TestModel")->translation = json_decode($data)->testParameter;
        return $data;
    }
}