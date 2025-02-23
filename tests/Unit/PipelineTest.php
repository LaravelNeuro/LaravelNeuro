<?php

use LaravelNeuro\Pipeline;
use PHPUnit\Framework\TestCase;

class PipelineTest extends TestCase
{
    public function testSetModel()
    {
        $pipeline = new Pipeline();
        $pipeline->setModel('testModel');

        $this->assertEquals('testModel', $pipeline->getModel());
    }

    public function testSetPrompt()
    {
        $pipeline = new Pipeline();
        $pipeline->setPrompt('Test prompt');

        $this->assertEquals('Test prompt', $pipeline->getPrompt());
    }

    public function testSetInvalidPrompt()
    {
        $pipeline = new Pipeline();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("For this pipeline, the paramater passed to setPrompt should be a string or an instance of SUAprompt.");
        $pipeline->setPrompt(["Arrays are an invalid Type"]);
    }
    
}
