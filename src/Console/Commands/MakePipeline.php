<?php

namespace LaravelNeuro\Console\Commands;

use Illuminate\Console\Command;

/**
 * Signature: lneuro:pipeline
 * 
 * Provides a console command to create a new LaravelNeuro Pipeline using a pre-built stub.
 * 
 * @package LaravelNeuro
 */
class MakePipeline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lneuro:pipeline {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new AI model pipeline class';

    /**
     * Execute the command.
     *
     */
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

    /**
     * Retrieves the llm-pipeline stub.
     *
     */
    protected function getStub() : string
    {
        return file_get_contents(__DIR__.'/../../resources/stubs/llm-pipeline.stub');
    }
}