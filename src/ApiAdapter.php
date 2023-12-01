<?php
namespace Kbirenheide\LaravelAi;

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

        return $this;
    }

    public function connect($method = 'GET')
    {
        try {

            $request = array_merge($this->request, ["stream" => $this->stream]);
            $this->response = $this->client->request($method, $this->api, ["json" => $request]);

        } catch (GuzzleException $e) {

            $this->error = $e;

        }
        
        return $this;
    }

    public function json()
    {
        if($this->error !== false) return $this->error;
        $response = $this->response;
        if ($this->stream) {
            return $this->yield($response, "json");
        } else {
            return $this->return($response, "json");
        }
    }

    public function array()
    {
        if($this->error !== false) return $this->error;
        $response = $this->response;
        if ($this->stream) {
            return $this->yield($response, "array");
        } else {
            return $this->return($response, "array");
        }
    }

    public function responseOnly()
    {
        if($this->error !== false) return $this->error;
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
