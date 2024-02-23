<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

use LaravelNeuro\LaravelNeuro\ApiAdapter;

use Tests\PackageTestCase;

class ApiAdapterTest extends PackageTestCase
{
    public function testSetApi()
    {
        $apiAdapter = new ApiAdapter();
        $apiAdapter->setApi('http://example.com/api');

        $this->assertEquals('http://example.com/api', $apiAdapter->getApi());
    }

    public function testSetClient()
    {
        $apiAdapter = new ApiAdapter();
        $apiAdapter->setClient();

        $this->assertTrue($apiAdapter->getClient() instanceof Client);
    }

    public function testSetUnsetHeaderEntry()
    {
        $apiAdapter = new ApiAdapter();
        $apiAdapter->setHeaderEntry("Content-Type", "application/json");
        $apiAdapter->setHeaderEntry("Accept-Charset", "utf-8");

        $this->assertEquals(["Content-Type" => "application/json",
                             "Accept-Charset" => "utf-8"], $apiAdapter->getHeaders());

        $apiAdapter->unsetHeaderEntry("Content-Type");

        $this->assertEquals(["Accept-Charset" => "utf-8"], $apiAdapter->getHeaders());
    }

    public function testApiCall()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true]))
        ]);

        $handlerStack = HandlerStack::create($mock);

        $apiAdapter = new ApiAdapter();
        $apiAdapter->setClient(['handler' => $handlerStack]);

        $apiAdapter->setApi('http://example.com/api');
        $response = $apiAdapter->output();

        $this->assertEquals('{"success":true}', (string) $response);
    }

    public function testFailingApiCallRequest()
    {
        $mock = new MockHandler([
            new Response(400, [], json_encode(['success' => false]))
        ]);

        $apiAdapter = new ApiAdapter();
        $handlerStack = HandlerStack::create($mock);
        $apiAdapter->setClient(['handler' => $handlerStack]);

        $apiAdapter->setApi('http://example.com/api');
        $this->expectException(Exception::class);
        $apiAdapter->output();

    }

    public function testFailingApiCallGeneral()
    {
        $mock = new MockHandler([
            new ConnectException("Error Connecting", new Request('GET', 'test'))
        ]);

        $apiAdapter = new ApiAdapter();
        $handlerStack = HandlerStack::create($mock);
        $apiAdapter->setClient(['handler' => $handlerStack]);

        $apiAdapter->setApi('http://example.com/api');
        $this->expectException(ConnectException::class);
        $apiAdapter->output();

    }

    public function testFileMake()
    {
        Storage::fake('lneuro');
        $apiAdapter = new ApiAdapter();
        $testAudioPath = __DIR__ . '/../resources/testaudio1.mp3';

        $lneuro = Storage::disk('lneuro');

        $lneuro->assertMissing('testaudio1.mp3');

        $result = $apiAdapter->fileMake('testaudio1.mp3', file_get_contents($testAudioPath));

        $this->assertIsArray($result, 'FileMake does not return an array.');

        $lneuro->assertExists($result['fileName']);

        $this->assertEquals(filesize($testAudioPath), $result['fileSize'],
        'Input file and output file have different file sizes.');

        $this->assertEquals(
            file_get_contents($testAudioPath),
            Storage::disk($result['diskName'])->get($result['fileName']),
            'Input file and output file are not identical.'
        );

        $this->assertEquals('audio/mpeg', $result['mimeType'],
            'Output file mime type incorrect.');
    }
}