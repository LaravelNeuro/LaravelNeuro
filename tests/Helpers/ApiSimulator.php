<?php

namespace Tests\Helpers;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\FulfilledPromise;

class ApiSimulator {

    private $mockHandler;
    private $currentCondition;
    private $responseQueue = [];

    public function __construct()
    {
        $this->mockHandler = new MockHandler();
    }

    public function expects($condition)
    {
        // Add logic to evaluate the condition against the incoming request
        // You can use a closure, a callback, or any other mechanism to evaluate the condition
        $this->currentCondition = $condition;
        return $this;
    }

    public function responds($response)
    {
        // Queue the response with the associated condition
        $this->responseQueue[] = ['condition' => $this->currentCondition, 'response' => $response];
        return $this;
    }

    public function getHandler()
    {
        $handlerStack = HandlerStack::create(function (RequestInterface $request, array $options) {
            foreach ($this->responseQueue as $responseItem) {
                if (call_user_func($responseItem['condition'], $request)) {
                    return new FulfilledPromise(
                        new Response(200, [], $responseItem['response']));
                }
                else
                {
                    return new FulfilledPromise(
                        new Response(400, [], json_encode(["error" => "request did not match set expectations.", "request" => (string)$request->getBody(), "response" => $responseItem['response']])));
                }
            }
            return $this->mockHandler->__invoke($request, $options);
        });

        return $handlerStack;
    }
}
