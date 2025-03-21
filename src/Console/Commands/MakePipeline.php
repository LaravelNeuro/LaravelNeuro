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
    protected $signature = 'lneuro:pipeline {name : The name of your Pipeline, which will be created in app/Pipielines.} 
                                            {--prompt : Create a custom prompt to be used by your pipeline.}
                                            {--driver : Create a custom driver to be used by your pipeline.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new AI model pipeline class and, optionally, prompts and drivers to be used by it.';

    /**
     * Execute the command.
     *
     */
    public function handle()
    {
        $name = $this->argument('name');
        $stub = $this->getStub('pipeline.stub');
        $modelTemplate = str_replace('{{modelName}}', $name, $stub);

        if($this->option('prompt')) {
            $promptClass = $name . 'Prompt';
            $this->createPrompt($promptClass);
            $modelTemplate = str_replace('{{usePrompt}}', 'App\\Pipelines\\Prompts\\' . $promptClass, $modelTemplate);
            $modelTemplate = str_replace('{{modelPrompt}}', $promptClass, $modelTemplate);
        }
        else {
            $modelTemplate = str_replace('{{usePrompt}}', 'LaravelNeuro\\Prompts\\BasicPrompt', $modelTemplate);
            $modelTemplate = str_replace('{{modelPrompt}}', 'BasicPrompt', $modelTemplate);
        }

        if($this->option('driver')) {
            $driverClass = $name . 'Driver';
            $this->createDriver($driverClass);            
            $modelTemplate = str_replace('{{useDriver}}', 'App\\Pipelines\\Drivers\\' . $driverClass, $modelTemplate);
            $modelTemplate = str_replace('{{modelDriver}}', $driverClass, $modelTemplate);
        }
        else {
            $modelTemplate = str_replace('{{useDriver}}', 'LaravelNeuro\\Drivers\\WebRequest\\GuzzleDriver', $modelTemplate);
            $modelTemplate = str_replace('{{modeDriver}}', 'GuzzleDriver', $modelTemplate);
        }

        $dirPath = app_path('Pipelines');

        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true); // true for recursive creation
        }

        $filePath = $dirPath . "/{$name}.php";

        if (!file_exists($filePath)) {
            file_put_contents($filePath, $modelTemplate);
            $this->info("AI Model {$name} created successfully.");
        } else {
            $this->error("AI Model {$name} already exists.");    
        }

        return Command::SUCCESS;

    }

    private function createPrompt($name) 
    {
        $stub = $this->getStub('prompt.stub');
        $promptTemplate = str_replace('{{promptName}}', $name, $stub);

        $dirPath = app_path('Pipelines/Prompts');

        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true); // true for recursive creation
        }

        $filePath = $dirPath . "/{$name}.php";

        if (!file_exists($filePath)) {
            file_put_contents($filePath, $promptTemplate);
            $this->info("AI Model Prompt {$name} created successfully.");
        } else {
            $this->error("AI Model Prompt {$name} already exists. Skipping.");
        }
    }

    private function createDriver($name) 
    {
        $stub = $this->getStub('driver.stub');
        $driverTemplate = str_replace('{{driverName}}', $name, $stub);

        $dirPath = app_path('Pipelines/Drivers');

        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true); // true for recursive creation
        }

        $filePath = $dirPath . "/{$name}.php";

        if (!file_exists($filePath)) {
            file_put_contents($filePath, $driverTemplate);
            $this->info("AI Model Driver {$name} created successfully.");
        } else {
            $this->error("AI Model Driver {$name} already exists. Skipping.");
        }
    }

    /**
     * Retrieves the llm-pipeline stub.
     *
     */
    protected function getStub($stubName) : string
    {
        return file_get_contents(__DIR__.'/../../resources/stubs/' . $stubName);
    }
}