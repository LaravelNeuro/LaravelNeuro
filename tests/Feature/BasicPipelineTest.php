<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Utils;

use LaravelNeuro\Drivers\WebRequest\GuzzleDriver;
use LaravelNeuro\Pipelines\BasicPipeline;

use LaravelNeuro\Prompts\SUAprompt;

use Tests\PackageTestCase;
use Tests\Helpers\ApiSimulator;

class BasicPipelineTest extends PackageTestCase {

    public function testBasicPipeline(): void
    {
        $pipeline = new BasicPipeline(new GuzzleDriver);

        $prompt = new SUAprompt;

        $prompt->pushSystem("test role.");
            $prompt->pushUser("prompt example.");
            $prompt->pushAgent("example response.");
            $prompt->pushUser("prompt example.");

        $mock = new ApiSimulator;
        $mock->expects(function ($request) {
            $body = (string) $request->getBody();
                    if(json_validate($body))
                    {
                        if(json_decode($body)->system === 'test role.') 
                            return true;
                        else
                            return false;
                    }
                    else
                        return false;
                })
             ->responds(json_encode(['response' => 'mocked response']));

        $handlerStack = ['handler' => $mock->getHandler()];

        $driver = $pipeline->driver();
            if ($driver instanceof GuzzleDriver) {
                $driver->setApi("mockAPI")
                       ->setClient($handlerStack);
            }

        $pipeline->setModel("testModel")
                 ->setPrompt($prompt);

        try
        {
            $successfulResponse = (json_decode($pipeline->output())->response === 'mocked response');
        }
        catch(\Exception $e)
        {
            $this->assertTrue(false, 'Successful mockAPI response expected, got unsuccessful: ' . $e);
        }
  
        $this->assertTrue($successfulResponse, 'Pipeline Request unsuccessful for method "output".');

        $streamContent = "data: " . json_encode(['response' => 'mocke']) . "\n";
        $streamContent .= "data: " . json_encode(['response' => 'd response']) . "\n";
        $resource = fopen('php://memory','r+');
        fwrite($resource, $streamContent);
        rewind($resource);
        $stream = Utils::streamFor($resource);

        $mock = new ApiSimulator;
        $mock->expects(function ($request) {
            $body = (string) $request->getBody();
                    if(json_validate($body))
                    {
                        if(json_decode($body)->system === 'test role.') 
                            return true;
                        else
                            return false;
                    }
                    else
                        return false;
                })
             ->responds($stream);

        $handlerStack = ['handler' => $mock->getHandler()];

        $driver = $pipeline->driver();
            if ($driver instanceof GuzzleDriver) {
                $driver->setApi("mockAPI")
                       ->setClient($handlerStack);
            }

        $pipeline->setModel("testModel")
                 ->setPrompt($prompt);

        try
        { 
            $output = $pipeline->stream();
            $data = '';
            foreach($output as $chunk)
            {
                $data .= json_decode($chunk)->response;
            }

            $successfulResponse2 = ($data === 'mocked response');
        }
        catch(\Exception $e)
        {
            $this->assertTrue(false, 'Successful mockAPI response expected, got unsuccessful: ' . $e);
        }
        
        $this->assertTrue($successfulResponse2, 'Pipeline request usuccessful for method "stream".');

        $prompt = new SUAprompt;

        $prompt->pushSystem("invalid test role.");
            $prompt->pushUser("prompt example.");
            $prompt->pushAgent("example response.");
            $prompt->pushUser("prompt example.");
            
        $pipeline->setPrompt($prompt);

        
        try
        { 
            $output = $pipeline->stream();
            $data = '';
            foreach($output as $chunk)
            {
                $data .= json_decode($chunk)->response;
            }

            $failingResponse1 = ($data === 'mocked response');
        }
        catch(\Exception $e)
        {
            $failingResponse1 = true;
        }

        ob_start();
        if ($driver instanceof GuzzleDriver) {
            $driver->debug();
        }
        
        try
        {
            $failingResponse2 = (json_decode($pipeline->output())->response !== 'mocked response');
        }
        catch(\Exception $e)
        {
            $failingResponse2 = true;
        }
        $debug = ob_get_contents();
        ob_end_clean();

        $expectedOutput = trim(preg_replace('/\s+/', ' ', '#API-Adapter
            Headers: 
                Array
                (
                )
                
                Request: 
                Array
                (
                    [model] => testModel
                    [system] => invalid test role.
                    [prompt] => prompt example.
                example response.
                prompt example.
                
                )'));
        $actualOutput = trim(preg_replace('/\s+/', ' ', $debug));

        $this->assertEquals($expectedOutput, $actualOutput,'Debug mode for ApiAdapter does not provide expected output.');

        $this->assertTrue($failingResponse1, 'Invalid stream request was somehow successful.');
        $this->assertTrue($failingResponse2, 'Invalid output request was somehow successful.');
    }
}