<?php
namespace Kbirenheide\L3MA;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ApiAdapter {

    protected $client;
    protected $api;
    protected $request = [];
    protected $response;
    protected $stream = false;
    protected $error = false;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function api($address)
    {
        $this->api = $address;

        return $this;
    }

    public function stream()
    {
        $this->stream = true;
        $this->request["stream"] = true;

        return $this;
    }

    private function connect($method)
    {
        try {
            $response = $this->client->request($method, $this->api, ["json" => $this->request, "stream" => $this->stream]);

        } catch (GuzzleException $e) {

            $this->error = $e;

        }
        
        return $response;
    }

    public function json($method = 'POST')
    {
        if($this->error !== false) return $this->error;
        $response = $this->connect($method);
        if ($this->stream) {
            return $this->yield($response, "json");
        } else {
            return $this->return($response, "json");
        }
    }

    public function array($method = 'POST')
    {
        if($this->error !== false) return $this->error;
        $response = $this->connect($method);
        if ($this->stream) {
            return $this->yield($response, "array");
        } else {
            return $this->return($response, "array");
        }
    }

    public function responseOnly($method = 'POST')
    {
        if($this->error !== false) return $this->error;
        $response = $this->connect($method);
        if ($this->stream) {
            return $this->yield($response, "text");
        } else {
            return $this->return($response, "text");
        }
    }    

    private function return($response, $type)
    {
        $body = $response->getBody();
        switch($type)
            {
                case "text":
                    return json_decode($body)->response;
                    break;
                case "array":
                    return json_decode($body);
                    break;
                case "json":
                    return $body;
                    break;
                default:
                    return $body;
                    break;
            } 
    }  

    private function yield($response, $type)
    {
        $buffer = '';
        $body = $response->getBody();

        while (!$body->eof()) {
            $buffer .= $body->read(10);

            while (($breakPosition = strpos($buffer, "\n")) !== false) {
                $jsonString = substr($buffer, 0, $breakPosition);
                $buffer = substr($buffer, $breakPosition + 1);

                $jsonObject = json_decode($jsonString, true);
                if ($jsonObject) {
                    $jsonObject = (object) $jsonObject;
                    switch($type)
                        {
                            case "text":
                                yield $jsonObject->response;
                                break;
                            case "array":
                                yield $jsonObject;
                                break;
                            case "json":
                                yield json_encode($jsonObject, JSON_PRETTY_PRINT);
                                break;
                            default:
                                yield $jsonObject;
                                break;
                        } 
                }
            }
        }
    }
}
