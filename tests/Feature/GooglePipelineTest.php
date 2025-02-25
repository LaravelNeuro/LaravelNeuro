<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

use LaravelNeuro\Drivers\WebRequest\GuzzleDriver;
use LaravelNeuro\Pipelines\Google\Multimodal;

use LaravelNeuro\Prompts\SUAprompt;
use LaravelNeuro\Prompts\PNSQFprompt;
use LaravelNeuro\Prompts\IVFSprompt;

use LaravelNeuro\Enums\PNSQFquality;

use Tests\PackageTestCase;
use Tests\Helpers\ApiSimulator;

use function PHPUnit\Framework\assertEquals;

class GooglePipelineTest extends PackageTestCase {

    public function testMultimodalNoApiKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("No Google Gemini API access token has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        new Multimodal;
    }

    public function testChatCompletion(): void
    {
        Config::set('laravelneuro.keychain.google', 'fake-api-key');

        $pipeline = new Multimodal(new GuzzleDriver);

        $this->assertTrue($pipeline instanceof Multimodal, 'Google Multimodal Pipeline instantiation unsuccessful.');

        $prompt = new SUAprompt;

        $prompt->pushSystem("You are a helpful assistant.");
        $prompt->pushUser("Hello!");

        $mock = new ApiSimulator;
        $mock->expects(function ($request) {
            $body = (string) $request->getBody();
                    if(json_validate($body))
                    {
                        $requestData = json_decode($body);
                        if( $requestData->system_instruction->parts->text === 'You are a helpful assistant.'
                            && count($requestData->contents) === 1) 
                            return true;
                        else
                            return false;
                    }
                    else
                        return false;
                })
             ->responds('{
                            "candidates": [
                                {
                                    "content": {
                                        "parts": [
                                            {
                                                "text": "Hello! How can I help you today?"
                                            }
                                        ],
                                        "role": "model"
                                    },
                                    "finishReason": "STOP",
                                    "index": 0,
                                    "safetyRatings": [
                                        {
                                            "category": "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                                            "probability": "NEGLIGIBLE"
                                        },
                                        {
                                            "category": "HARM_CATEGORY_HATE_SPEECH",
                                            "probability": "NEGLIGIBLE"
                                        },
                                        {
                                            "category": "HARM_CATEGORY_HARASSMENT",
                                            "probability": "NEGLIGIBLE"
                                        },
                                        {
                                            "category": "HARM_CATEGORY_DANGEROUS_CONTENT",
                                            "probability": "NEGLIGIBLE"
                                        }
                                    ]
                                }
                            ],
                            "usageMetadata": {
                                "promptTokenCount": 9,
                                "candidatesTokenCount": 12,
                                "totalTokenCount": 21
                            }
                        }');

        $handlerStack = ['handler' => $mock->getHandler()];

        $driver = $pipeline->driver();
            if ($driver instanceof GuzzleDriver) {
                $driver->setClient($handlerStack);
            }

        $pipeline->setPrompt($prompt);

        try
        {
            $successfulResponse = ($pipeline->output() === 'Hello! How can I help you today?');
        }
        catch(\Exception $e)
        {
            $this->assertTrue(false, 'Successful mockAPI response expected, got unsuccessful: ' . $e);
        }

        $this->assertTrue($successfulResponse, 'Unsuccessful pipeline request.');

        try
        {
            $successfulResponse = (json_validate($pipeline->json()));
        }
        catch(\Exception $e)
        {
            $this->assertTrue(false, 'Successful mockAPI response expected, got unsuccessful: ' . $e);
        }

        $this->assertTrue($successfulResponse, 'Unsuccessful pipeline request, json.');

        try
        {
            $successfulResponse = ($pipeline->array()["candidates"][0]["content"]["parts"][0]["text"] === 'Hello! How can I help you today?');
        }
        catch(\Exception $e)
        {
            $this->assertTrue(false, 'Successful mockAPI response expected, got unsuccessful: ' . $e);
        }

        $this->assertTrue($successfulResponse, 'Unsuccessful pipeline request, array.');
    }
}