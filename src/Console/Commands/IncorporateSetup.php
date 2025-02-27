<?php

namespace LaravelNeuro\Console\Commands;

use Illuminate\Console\Command;
use LaravelNeuro\Networking\Incorporate;
use LaravelNeuro\Enums\IncorporatePrebuild;

/**
 * Signature: lneuro:prebuild
 * Creates the necessary folder, folder structure, and setup file for a new Laravel Neuro Agent Network (called "Corporation"). 
 * The setup file will be a JSON file by default, but a PHP file can also be created if the --php flag is passed. 
 * The PHP file utilizes the Incorporate builder pattern directly to build your Corporation.
 * 
 * @package LaravelNeuro
 */
class IncorporateSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lneuro:prebuild 
                                                {name : This will be the namespace and folder name of your new Laravel Neuro Corporation} 
                                                {--php : If this flag is true, a setup.php will be created instead of the default setup.json. This php file utilizes the Incorporate builder pattern directly to build your Corporation.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the namespace, folder, and setup file for a new Laravel Neuro Agent Network (called "Corporation").';

    /**
     * Execute the command.
     *
     */
    public function handle()
    {
        $this->output->getFormatter()->setStyle('cyan', new \Symfony\Component\Console\Formatter\OutputFormatterStyle('cyan'));

        $namespace = $this->argument('name');
        $namespace = ucwords($namespace);
        $namespace = str_replace(' ', '', $namespace);
        $namespace = preg_replace('/[^a-zA-Z0-9\/_\\\\]/', '_', $namespace);

        if($this->option('php'))
        {
            $prebuildType = IncorporatePrebuild::PHP;
        }
        else
        {
            $prebuildType = IncorporatePrebuild::JSON;
        }
        
        if(Incorporate::prebuild($namespace, $prebuildType))
        {
            $this->info("Your setup file has successfully been created in app/Corporations/$namespace.");
            $this->line("Once you have filled it out, you can install your Corporation using the artisan command", "cyan");
            $this->line('<bg=bright-blue> '."artisan lneuro:install $namespace".'  </>'); 
            $this->line("then, you may run it using", "cyan") ;
            $this->line('<bg=bright-blue> '."artisan lneuro:run $namespace {task}".'  </>'); 
            $this->line("or by programmatically calling the run() method on a new App\\Corporations\\$namespace object. Make sure to pass your task string as the first parameter to your new App\\Corporations\\$namespace's constructor.", "cyan");
            return Command::SUCCESS;
        }
        else
        {
            $this->error("The setup creation process has failed. Either an invalid setup file type was passed, or, much more likely, a setup file of the chosen type already exists in the chosen namespace.\n");
            return Command::FAILURE;
        }

    }
}