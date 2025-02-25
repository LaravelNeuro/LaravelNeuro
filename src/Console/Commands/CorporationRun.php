<?php

namespace LaravelNeuro\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use LaravelNeuro\Networking\Corporation;

class CorporationRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lneuro:run 
                                        {namespace : The namespace of the to-be-run Corporation} 
                                        {task="" : The task your corporation should perform}
                                        {--s|save-history : Save the history of the Corporation run to the database.}
                                        {--d|debug : Get the full runtime output of your Corporation as it resolves this run.}
                                        {--m|with-migrations : Execute corporation-specific migrations before run}
                                        ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pass a task to a Laravel Neuro Corporation.';

    /**
     * Execute the command.
     *
     */
    public function handle()
    {
        $class = $this->argument('namespace');
        $defaultNamespace = config('laravelneuro.default_namespace', 'Corporations');
        $classDefault = 'App\\'.$defaultNamespace.'\\'.$class.'\\'.$class;
        $migrations = 'app/'.str_replace('\\', '/', $defaultNamespace).'/'.$class.'/Database/migrations';
        $task = $this->argument('task');

        if(Str::contains($class, '\\'))
        {
            $classElements = explode('\\', $class);
            $className = array_pop($classElements);
            $classNameSpace = $class . '\\' . $className;
        }
        else 
        {
            $classNameSpace = 'App'.'\\'.config('laravelneuro.default_namespace', 'Corporations').'\\'.$class.'\\'.$class;
        }

        if(!(class_exists($classNameSpace) && (new $classNameSpace("testing", false, true)) instanceof Corporation))
        {
            $this->error('The namespace you have passed does not point to a legal Corporation class. Is your Corporation properly installed and set up? If your Corporation is not in the default namespace of '.$classDefault.', you should pass the full Namespace to this command.');
            return Command::FAILURE;
        }
        
        if($this->option('with-migrations'))
        {
            $this->info('Running Corporation migrations to ensure all required tables exist.');
            $this->call('migrate', ['--path' => $migrations]);
        }  

        try{
            $init = new $classNameSpace(task: $task, saveHistory: $this->option('save-history'), debug: $this->option('debug'));
            $this->info('Corporation successfully initiated. Passing task to new Project, please wait.');
        }
        catch(\Exception $e)
        {
            echo $e;
            $this->error('Failed to instantiate Corporation:' . "\n\n" . $e);
            return Command::FAILURE;
        }
        
        try{
            $run = $init->run();
            $this->info('Run complete. NetworkProject instance:');
            $this->info($run);
            return Command::SUCCESS;
        }
        catch(\Exception $e)
        {
            $this->error('Failed to complete Run:' . "\n\n" . $e);
            return Command::FAILURE;
        }

    }
}