<?php

use LaravelNeuro\Pipelines\BasicPipeline;
use PHPUnit\Framework\TestCase;

class PipelineTest extends TestCase
{
    public function testSetModel()
    {
        $pipeline = new BasicPipeline();
        $pipeline->setModel('testModel');

        $this->assertEquals('testModel', $pipeline->getModel());
    }

    public function testSetPrompt()
    {
        $pipeline = new BasicPipeline();
        $pipeline->setPrompt('Test prompt');

        $this->assertEquals('Test prompt', $pipeline->getPrompt());
    }
    
}
