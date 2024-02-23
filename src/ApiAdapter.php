<?php
namespace LaravelNeuro\LaravelNeuro;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Storage;
use LaravelNeuro\LaravelNeuro\Enums\RequestType;

class ApiAdapter {

    protected $client = false;
    protected $api;
    protected $request;
    protected RequestType $requestType = RequestType::JSON;
    protected $response;
    protected $fileType;
    protected $stream = false;
    protected $error = false;
    protected $headers = [];
    protected $debug = false;

    public function debug()
    {
        $this->debug = true;
        return $this;
    }

    public function setHeaderEntry(string $key, string $value)
    {
        $this->headers[$key] = $value;
    }

    public function unsetHeaderEntry(string $key)
    {
        unset($this->headers[$key]);
    }

    public function getHeaders()
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

    public function setClient(array $options = [])
    {
        $this->client = new Client($options);
        return $this;
    }    

    public function getClient()
    {
        return $this->client;
    }

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
            $this->error = $e->getResponse()->getBody() . $e;
            throw new \Exception($e->getResponse()->getBody());
        } catch (\Exception $e) {
            // Handle broader range of exceptions
            $this->error = $e;
            throw $e;
        }

    }   

    public function fileMake($fileName, $data)
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

    public function stream()
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