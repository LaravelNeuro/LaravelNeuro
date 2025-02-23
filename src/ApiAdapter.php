<?php
namespace LaravelNeuro\LaravelNeuro;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Storage;
use LaravelNeuro\LaravelNeuro\Enums\RequestType;

/**
 * Class ApiAdapter
 *
 * Handles API requests using the GuzzleHttp client.
 * Provides functionality to send requests to various AI APIs,
 * process responses, handle streaming responses, and save files.
 *
 * @package LaravelNeuro\LaravelNeuro
 */
class ApiAdapter {

    /**
     * The GuzzleHttp client instance.
     *
     * @var Client|bool False if not yet initialized.
     */
    protected $client = false;

    /**
     * The API endpoint URL.
     *
     * @var string
     */
    protected $api;

    /**
     * The request payload or parameters.
     *
     * @var mixed
     */
    protected $request;

    /**
     * The type of request being made. JSON is the default case, MULTIPART is required 
     * for file streams, which can become relevant for speech-to-text, image-to-image, 
     * image-to-text, and similar models that require a file input.
     * 
     * @var RequestType 
     */
    protected RequestType $requestType = RequestType::JSON;

    /**
     * The API response.
     *
     * @var mixed
     */
    protected $response;

    /**
     * Indicates whether the response should be streamed.
     *
     * @var bool
     */
    protected $stream = false;

    /**
     * Stores any error encountered during the API request.
     *
     * @var mixed
     */
    protected $error = false;


    /**
     * An associative array of headers to send with the request.
     *
     * @var array
     */
    protected array $headers = [];

    /**
     * When true, debug information is printed during the request.
     *
     * @var bool
     */
    protected $debug = false;


    /**
     * Enables or disables debug mode.
     *
     * When enabled, the request data and headers are printed
     * to aid in debugging.
     *
     * @param bool $set True to enable debug mode; false to disable.
     * @return self
     */
    public function debug(bool $set = true) : self
    {
        $this->debug = $set;
        return $this;
    }

    /**
     * Sets a header entry to be sent with the API request.
     *
     * @param string $key   The header name.
     * @param string $value The header value.
     * @return self
     */
    public function setHeaderEntry(string $key, string $value) : self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Removes a header entry.
     *
     * @param string $key The header name to remove.
     * @return self
     */
    public function unsetHeaderEntry(string $key) : self
    {
        if(isset($this->headers[$key]))
            unset($this->headers[$key]);
        return $this;
    }

    /**
     * Retrieves the current header array.
     *
     * @return array An associative array of header entries.
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * Sets the API endpoint URL.
     *
     * @param string $address The API endpoint.
     * @return self
     */
    public function setApi($address)
    {
        $this->api = $address;

        return $this;
    }

    /**
     * Retrieves the API endpoint URL.
     *
     * @return string The API endpoint.
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * Retrieves the current request type.
     *
     * @return RequestType The current request type.
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * Sets the request type.
     *
     * @param RequestType $requestType The request type to set.
     * @return self
     */
    public function setRequestType(RequestType $requestType)
    {
        $this->requestType = $requestType;
        return $this;
    }

    /**
     * Sets a custom Guzzle client.
     *
     * Typically not required as a new Client is created if none is set.
     *
     * @param array $options The options for the Guzzle client constructor.
     * @return self
     */
    public function setClient(array $options = [])
    {
        $this->client = new Client($options);
        return $this;
    }    

    /**
     * Retrieves the current Guzzle client instance.
     *
     * @return Client|bool The Guzzle client or false if not initialized.
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Executes the API request using the Guzzle client.
     *
     * Builds the request based on the current request type (JSON or MULTIPART)
     * and handles streaming if enabled. Debug information is printed if debug mode is active.
     *
     * @param string $method The HTTP method to use (typically 'POST').
     * @return \Psr\Http\Message\ResponseInterface The API response.
     * @throws \Exception If the request fails.
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
     * Saves file data to a designated disk using Laravel's Storage facade.
     *
     * This method is useful for handling file responses (e.g., images or audio)
     * by storing the data on a configured disk.
     *
     * @param string $fileName The name to save the file as.
     * @param mixed $data The file data to be saved.
     * @return array An associative array of file metadata:
     *               - "fileName": The saved file name.
     *               - "diskName": The disk where the file is stored.
     *               - "fileSize": The file size in bytes.
     *               - "mimeType": The MIME type of the file.
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

    /**
     * Executes the API request and returns the response body.
     *
     * This method leverages the connect() method and retrieves the response body.
     *
     * @return mixed The response body.
     */
    public function output()
    {
        $response = $this->connect("POST");
        $body = $response->getBody();
        
        return $body; 
    }  

    /**
     * Executes a streaming API request.
     *
     * Enables streaming mode and returns a generator that yields JSON-encoded
     * data chunks from the API response. Useful for handling responses that
     * include large or streaming payloads.
     *
     * @return Generator Yields JSON-encoded data chunks.
     */
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