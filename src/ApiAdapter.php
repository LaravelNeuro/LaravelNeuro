<?php
namespace Kbirenheide\LaravelAI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ApiAdapter {

    protected $client;
    protected $api;
    protected $request = [];
    protected $response;
    protected $stream = false;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function api($address)
    {
        $this->api = $address;

        return $this;
    }

    public function stream()
    {
        $this->stream = true;

        return $this;
    }

    public function connect($method = 'GET')
    {
        try {

            $request = $this->stream ? array_merge($this->request, ['stream' => true]) : $this->request;
            $this->response = $this->client->request($method, $this->api, $request);

        } catch (GuzzleException $e) {

            return ["error" => $e];

        }
        
        return $this;
    }

    public function json()
    {
        $response = $this->response;
        if ($this->stream) {
            return $this->yield($response, "json");
        } else {
            return $this->return($response, "json");
        }
    }

    public function array()
    {
        $response = $this->response;
        if ($this->stream) {
            return $this->yield($response, "array");
        } else {
            return $this->return($response, "array");
        }
    }

    public function responseOnly()
    {
        $response = $this->response;
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
        $body = $response->getBody();
        while(!$body->eof())
        {
            switch($type)
            {
                case "text":
                    yield json_decode($body->read(1024))->response;
                    break;
                case "array":
                    yield json_decode($body);
                    break;
                case "json":
                    yield $body->read(1024);
                    break;
                default:
                    yield $body->read(1024);
                    break;
            } 
        }
    }
}
