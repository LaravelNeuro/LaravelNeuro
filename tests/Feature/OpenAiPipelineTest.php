<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

use LaravelNeuro\Pipeline;
use LaravelNeuro\Pipelines\OpenAI\ChatCompletion;
use LaravelNeuro\Pipelines\OpenAI\AudioTTS;
use LaravelNeuro\Pipelines\OpenAI\DallE;

use LaravelNeuro\Prompts\SUAprompt;
use LaravelNeuro\Prompts\PNSQFprompt;
use LaravelNeuro\Prompts\IVFSprompt;

use LaravelNeuro\Enums\PNSQFquality;

use Tests\PackageTestCase;
use Tests\Helpers\ApiSimulator;

use function PHPUnit\Framework\assertEquals;

class OpenAiPipelineTest extends PackageTestCase {

    public function testChatCompletionNoApiKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("No OpenAI access token has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        new Chatcompletion;
    }

    public function testAudioTtsNoApiKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("No OpenAI access token has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        new AudioTTS;
    }

    public function testDallEnoApiKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("No OpenAI access token has been set for this pipeline in the LaravelNeuro config file (app/config/laravelneuro.php).");
        new DallE;
    }

    public function testChatCompletion(): void
    {
        Config::set('laravelneuro.keychain.openai', 'fake-api-key');

        $pipeline = new ChatCompletion;

        $this->assertTrue($pipeline instanceof Chatcompletion, 'OpenAI ChatCompletion Pipeline instantiation unsuccessful.');

        $prompt = new SUAprompt;

        $prompt->pushSystem("You are a helpful assistant.");
            $prompt->pushUser("Hello!");

        $mock = new ApiSimulator;
        $mock->expects(function ($request) {
            $body = (string) $request->getBody();
                    if(json_validate($body))
                    {
                        $requestData = json_decode($body);
                        if( $requestData->model === config('laravelneuro.models.gpt-3-5-turbo.model')
                            && count($requestData->messages) === 2) 
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
                    "content": "Hello there, how may I assist you today?"
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

        $pipeline->setPrompt($prompt)
                 ->setClient($handlerStack);

        try
        {
            $successfulResponse = ($pipeline->output() === 'Hello there, how may I assist you today?');
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
            $successfulResponse = ($pipeline->array()["choices"][0]["message"]["content"] === 'Hello there, how may I assist you today?');
        }
        catch(\Exception $e)
        {
            $this->assertTrue(false, 'Successful mockAPI response expected, got unsuccessful: ' . $e);
        }

        $this->assertTrue($successfulResponse, 'Unsuccessful pipeline request, array.');
    }

    public function testAudioTTS()
    {
        Config::set('laravelneuro.keychain.openai', 'fake-api-key');

        $pipeline = new AudioTTS;

        $this->assertTrue($pipeline instanceof AudioTTS, 'OpenAI AudioTTS Pipeline could not be instantiated.');

        $prompt = new IVFSprompt;

        $prompt->setInput("Dies ist ein TTS test.");
            $prompt->setVoice("onyx");
            $prompt->setFormat("mp3");
            $prompt->settings([]);

        Storage::fake('lneuro');

        $testAudioPath = __DIR__ . '/../resources/testaudio1.mp3';

        $mock = new ApiSimulator;
        $mock->expects(function ($request) {
            $body = (string) $request->getBody();
                    if(json_validate($body))
                    {
                        $requestData = json_decode($body);
                        if($requestData->input == "Dies ist ein TTS test."
                        && $requestData->voice == "onyx"
                        && $requestData->response_format == "mp3"
                        && $requestData->model == "tts-1") 
                            return true;
                        else
                            return false;
                    }
                    else
                        return false;
                })
             ->responds(file_get_contents($testAudioPath));

        $handlerStack = ['handler' => $mock->getHandler()];

        $pipeline->setPrompt($prompt)
                 ->setClient($handlerStack);

        $output = $pipeline->output();

        $this->assertTrue(json_validate($output), 'AudioTTS Pipeline did not return valid json.');

        $this->assertEquals(json_decode($output)->diskName, "lneuro");
        $this->assertTrue(is_string(json_decode($output)->fileName));
        $this->assertEquals(json_decode($output)->fileSize, filesize($testAudioPath));
        $this->assertEquals(json_decode($output)->mimeType, "audio/mpeg");
        $this->assertEquals(Storage::disk(json_decode($output)->diskName)->get(json_decode($output)->fileName), file_get_contents($testAudioPath), 'AudioTTS Pipeline returned file not equal with input file.');
    }

    public function testDallE()
    {
        Config::set('laravelneuro.keychain.openai', 'fake-api-key');

        $pipeline = new DallE;

        $this->assertTrue($pipeline instanceof DallE, 'DallE Pipeline could not be instantiated.');

        $prompt = new PNSQFprompt;

        $prompt->setPrompt("A digital painting of artificial intelligence attaining consciousness.");
        $prompt->setNumber(2);
        $prompt->setSize(...[1024, 1024]);
        $prompt->setQuality(PNSQFquality::STANDARD);
        $prompt->setFormat('b64_json');

        Storage::fake('lneuro');

        $testImagePath = __DIR__ . '/../resources/testimage1.png';

        $mock = new ApiSimulator;
        $mock->expects(function ($request) {
            $body = (string) $request->getBody();
                    if(json_validate($body))
                    {
                        $requestData = json_decode($body);
                        if($requestData->n == 2
                        && $requestData->model == "dall-e-2"
                        && $requestData->size == "1024x1024"
                        && $requestData->quality == "standard"
                        && $requestData->response_format == "b64_json") 
                            return true;
                        else
                            return false;
                    }
                    else
                        return false;
                })
             ->responds(json_encode([
                "created" => time(),
                "data" => [
                    ["b64_json" => base64_encode(file_get_contents($testImagePath))],
                    ["b64_json" => base64_encode(file_get_contents($testImagePath))]
                    ]
                ]));

        $handlerStack = ['handler' => $mock->getHandler()];

        $pipeline->setPrompt($prompt)
                 ->setClient($handlerStack);

        $output = $pipeline->output();

        $this->assertTrue(json_validate($output), 'Image Pipeline did not return valid json.');

        foreach(json_decode($output) as $data)
        {
            $this->assertEquals($data->diskName, "lneuro");
            $this->assertTrue(is_string($data->fileName));
            $this->assertEquals($data->fileSize, filesize($testImagePath));
            $this->assertEquals($data->mimeType, "image/png");
            $this->assertEquals(Storage::disk($data->diskName)->get($data->fileName), file_get_contents($testImagePath));
        }
    }
}