<?php
namespace LaravelNeuro\Drivers\WebRequest;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Storage;
use LaravelNeuro\Enums\RequestType;
use LaravelNeuro\Contracts\AiModel\Driver;

/**
 * A concrete implementation of the Driver interface that uses GuzzleHttp
 * to send API requests. This driver supports both standard and streaming
 * requests, and can also handle file responses via Laravel's Storage facade.
 *
 * @package LaravelNeuro
 */
class GuzzleDriver implements Driver {

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
     * The type of request being made.
     * Defaults to JSON. MULTIPART is used for file streams.
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
     * The key used in the request payload to store the prompt.
     *
     * @var string
     */
    protected $promptKey = "prompt";

    /**
     * When true, debug information is printed during the request.
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Enables or disables debug mode.
     *
     * When enabled, prints request data and headers for debugging purposes.
     *
     * @param bool $set True to enable debug mode, false to disable.
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
        return $this->headers ?? null;
    }

    /**
     * Sets the API endpoint URL.
     *
     * @param string $address The API endpoint.
     * @return self
     */
    public function setApi(string $address) : self
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
        return $this->api ?? null;
    }

    /**
     * Modifies the request payload.
     *
     * If an array is provided, appends it to the request array.
     * Otherwise, sets the given key to the provided value.
     *
     * @param mixed $key_or_array The key to set or an array to merge.
     * @param mixed $value        The value to assign if a key is provided.
     * @return self
     */
    public function modifyRequest($key_or_array, $value=null) : self
    {
        if(is_array($key_or_array))
            $this->request = $key_or_array;
        else
            $this->request[$key_or_array] = $value;

        return $this;
    }

    /**
     * Sets the model for the request.
     *
     * @param mixed $model The model identifier.
     * @return self
     */
    public function setModel($model) : self
    {
        $this->modifyRequest("model", $model);
        return $this;
    }

    /**
     * Retrieves the model from the request payload.
     *
     * @return string|null The model identifier, or null if not set.
     */
    public function getModel() : string
    {
        return $this->request["model"] ?? null;
    }

    /**
     * Sets the system prompt for the request.
     *
     * @param mixed $system The system prompt.
     * @return self
     */
    public function setSystemPrompt($system) : self
    {
        $this->modifyRequest("system", $system);
        return $this;
    }

    /**
     * Retrieves the system prompt from the request payload.
     *
     * @return mixed The system prompt, or null if not set.
     */
    public function getSystemPrompt()
    {
        return $this->request["system"] ?? null;
    }


    /**
     * Sets the prompt for the request.
     *
     * @param mixed  $prompt The prompt text.
     * @param string $key    Optional key to use for the prompt (defaults to "prompt").
     * @return self
     */
    public function setPrompt($prompt, $key = "prompt") : self
    {
        $this->promptKey = $key;
        $this->modifyRequest($key, $prompt);
        return $this;
    }

    /**
     * Retrieves the prompt from the request payload.
     *
     * @return mixed The prompt, or null if not set.
     */
    public function getPrompt()
    {
        return $this->request[$this->promptKey] ?? null;
    }

    /**
     * Retrieves the entire request payload or a specific key.
     *
     * @param string|null $key Optional key to retrieve from the request.
     * @return mixed The entire request payload or the value at the specified key.
     */
    public function getRequest($key = null)
    {
        if ($key) {
            return $this->request[$key] ?? null;
        }
        return $this->request ?? null;
    }

    /**
     * Retrieves the current request type.
     *
     * @return RequestType The current request type.
     */
    public function getRequestType()
    {
        return $this->requestType ?? null;
    }

    /**
     * Sets the request type.
     *
     * @param RequestType $requestType The request type to set.
     * @return self
     */
    public function setRequestType(RequestType $requestType) : self
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
        return $this->client ?? false;
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
                    $response = $client->request("POST", $this->api, ["headers" => $this->headers, "multipart" => $this->request]);
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