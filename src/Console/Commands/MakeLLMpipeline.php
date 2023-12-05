<?php

namespace Kbirenheide\LaravelNeuro\Console\Commands;

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

        $dirPath = app_path('Pipelines/LLM');

        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true); // true for recursive creation
        }

        $filePath = $dirPath . "/{$name}.php";

        $filePath = app_path("Pipelines/LLM/{$name}.php");
        if (!file_exists($filePath)) {
            file_put_contents($filePath, $modelTemplate);
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