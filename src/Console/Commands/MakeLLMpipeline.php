<?php

namespace Kbirenheide\L3MA\Console\Commands;

use Illuminate\Console\Command;

class MakeLLMpipeline extends Command
{
    protected $signature = 'make:llmpipe {name}';
    protected $description = 'Create a new AI model pipeline class';

    public function handle()
    {
        $name = $this->argument('name');
        $stub = $this->getStub();
        $modelTemplate = str_replace('{{modelName}}', $name, $stub);

        $path = app_path("Pipelines/LLM/{$name}.php");
        if (!file_exists($path)) {
            file_put_contents($path, $modelTemplate);
            $this->info("AI Model {$name} created successfully.");
        } else {
            $this->error("AI Model {$name} already exists.");
        }
    }

    protected function getStub()
    {
        return file_get_contents(__DIR__.'/../../resources/stubs/llm-pipeline.stub');
    }
}