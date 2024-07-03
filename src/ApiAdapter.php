<?php
namespace LaravelNeuro\LaravelNeuro;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Storage;
use LaravelNeuro\LaravelNeuro\Enums\RequestType;

/**
 * Handles API requests using GuzzleHttp client.
 * This class provides functionality to send requests to various AI APIs,
 * process the responses, and handle different request types and errors.
 */
class ApiAdapter {

    /**
     * @var Client|bool The GuzzleHttp client instance or false if not initialized.
     */
    protected $client = false;

    /**
     * @var string The API endpoint or service being requested.
     */
    protected $api;

    /**
     * @var mixed The request payload or parameters.
     */
    protected $request;

    /**
     * @var RequestType The type of request being made. JSON is the default case, MULTIPART is required for file streams, which can become relevant for speech-to-text, image-to-image, image-to-text, and similar models that require a file input.
     */
    protected RequestType $requestType = RequestType::JSON;

    protected $response;

    /**
     * @var bool Determines whether the response should be a streaming response.
     */
    protected $stream = false;

    protected $error = false;

    /**
     * @var array Any headers that should be passed to the request. Headers should be entered as key-value pairs, ideally using the setHeaderEntry method.
     */
    protected array $headers = [];

    /**
     * @var bool when set to true the request and headers will be printed out when the connect method is called. This can be helpful when building custom pipelines but having trouble setting the curl parameters to comply with the target API.
     */
    protected $debug = false;

    /**
     * @param bool $set Is true by default and does not usually need to be set.
     * @return self This chainable method enables or disables the $debug member.
     */
    public function debug(bool $set = true) : self
    {
        $this->debug = $set;
        return $this;
    }

    public function setHeaderEntry(string $key, string $value) : self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function unsetHeaderEntry(string $key) : self
    {
        if(isset($this->headers[$key]))
            unset($this->headers[$key]);
        return $this;
    }

    public function getHeaders() : array
    {
        return $this->headers;
    }

    public function setApi($address)
    {
        $this->api = $address;

        return $this;
    }

    public function getApi()
    {
        return $this->api;
    }

    public function getRequestType()
    {
        return $this->requestType;
    }

    public function setRequestType(RequestType $requestType)
    {
        $this->requestType = $requestType;
        return $this;
    }

    /**
     * Set a custom Guzzle Client. Not usually necessary since the connect method creates a fresh Client instance when non has been set before.
     * 
     * @param array $options the Guzzle Client object's constructor options.
     * @return self Chainable method.
     */
    public function setClient(array $options = [])
    {
        $this->client = new Client($options);
        return $this;
    }    

    public function getClient()
    {
        return $this->client;
    }

    /**
     * Contains the request execution logic for the API request using the Guzzle Client's request method.
     * 
     * @param mixed $method This will almost always be 'POST'.
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function connect($method)
    {
        if($this->client === false) $this->client = new Client();
        $response = false;
        try {

            $client = $this->client;
            if($this->debug)
            {
                echo "#API-Adapter\n";
                echo "\tHeaders: \n";
                print_r($this->headers);
                echo "\nRequest: \n";
                print_r($this->request);
            }
            switch($this->getRequestType())
            {
                case RequestType::JSON:
                    $response = $client->request($method, $this->api, ["json" => $this->request, "stream" => $this->stream, "headers" => $this->headers]);
                    break;
                case RequestType::MULTIPART:                
                    $response = $client->post($this->api, ["headers" => $this->headers, "multipart" => $this->request]);
                    break;
                default:
                    $response = $client->request($method, $this->api, ["json" => $this->request, "stream" => $this->stream, "headers" => $this->headers]);
                    break;
            }
            
            return $response;

        } catch (RequestException $e) {
            $this->error = ($e->getResponse()->getBody() ?? '') . $e;
            throw new \Exception($this->error);
        } catch (\Exception $e) {
            // Handle broader range of exceptions
            $this->error = $e;
            throw $e;
        }

    }   

    /**
     * Some models return files, such as image generation models. This method will use the Laravel Storage facade to save incoming data to the lneuro disk, defined in ./config/filesystems.php
     * 
     * @param mixed $method This will almost always be 'POST'.
     * @return array Returns an array with the following meta data of the saved file:
     *    => "fileName"
     *    => "diskName"
     *    => "fileSize"
     *    => "mimeType"
     */
    public function fileMake(string $fileName, $data)
    {
        $determineMimeType = function ($fileName)
        {
            $extensionToMimeType = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'pdf' => 'application/pdf',
                'mp3' => 'audio/mpeg',
                'wav' => 'audio/wav',
                'opus' => 'audio/opus',
                'aac' => 'audio/aac',
                'flac' => 'audio/flac',
                'webp' => 'image/webp',
                'txt' => 'text/plain',
                'csv' => 'text/csv',
                'json' => 'application/json',
                // Add more mappings as needed
            ];
        
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
            return $extensionToMimeType[$extension] ?? 'application/octet-stream';
        };

        $diskName = 'lneuro';
        Storage::disk($diskName)->put($fileName, $data);
    
        $fileMetaData = [
            "fileName" => $fileName,
            "diskName" => $diskName,
            "fileSize" => Storage::disk($diskName)->size($fileName),
            "mimeType" => $determineMimeType($fileName),
        ];
    
        return $fileMetaData;
    }

    public function output()
    {
        $response = $this->connect("POST");
        $body = $response->getBody();
        
        return $body; 
    }  

    public function stream() : Generator
    {
        $this->stream = true;
        $response = $this->connect("POST");
        $buffer = '';

            $body = $response->getBody();

            $firstPackage = true;

            while (!$body->eof() || $firstPackage) {
                $firstPackage = false;
                $buffer .= $body->read(10);

                while (($breakPosition = strpos($buffer, "\n")) !== false) {
                    $jsonString = substr($buffer, 0, $breakPosition);
                    $buffer = substr($buffer, $breakPosition + 1);

                    if (strpos($jsonString, 'data: ') === 0) $jsonString = substr($jsonString, 6);

                    $jsonObject = json_decode($jsonString);
                    if ($jsonObject) {
                        yield json_encode($jsonObject, JSON_PRETTY_PRINT);
                    }
                }
            }
    }
}