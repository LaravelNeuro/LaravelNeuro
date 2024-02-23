<?php

namespace LaravelNeuro\LaravelNeuro\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use LaravelNeuro\LaravelNeuro\Networking\Incorporate;
use LaravelNeuro\LaravelNeuro\Enums\IncorporatePrebuild;

class IncorporateInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lneuro:install 
                                                {namespace : This is the namespace and folder name of your Laravel Neuro Corporation, where your setup script created with lneuro:prebuild (or by hand)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use the setup file in your Laravel Neuro Corporation\'s folder to create the necessary scripts and tables to run your Corporation.';

    /**
     * Execute the command.
     *
     */
    public function handle()
    {
        $this->output->getFormatter()->setStyle('cyan', new \Symfony\Component\Console\Formatter\OutputFormatterStyle('cyan'));

        $nameSpace = $this->argument('namespace');
        $nameSpace = ucwords($nameSpace);
        $nameSpace = str_replace(' ', '', $nameSpace);
        $nameSpace = preg_replace('/[^a-zA-Z0-9\/_\\\\]/', '_', $nameSpace);
        if(Str::contains($nameSpace, '\\'))
        {
            $destination = Str::replace('\\', '/', $nameSpace); // Convert backslashes to forward slashes  
            if (!Str::startsWith($destination, 'App/')) {
                throw new \InvalidArgumentException("Any fully qualified Namespace must start with 'App\\'. If you input just a name, the default Namespace of App\\Corporations will be used.");
            }
            $destination = Str::after($destination, 'App/'); // Remove 'App/' prefix
        }
        else
        {
            $destination = config('laravelneuro.default_namespace', 'Corporations') . '/' . $nameSpace;
        }
        
        $folder = app_path($destination);

        if (file_exists($folder.'/setup.json')) 
        {
            $corporation = Incorporate::installFromJSON(file_get_contents($folder.'/setup.json'));       
            $this->info(json_encode($corporation, true));
            $this->info("Your Corporation has been installed successfully.");
            return Command::SUCCESS;
        }
        elseif (file_exists($folder.'/setup.php')) 
        {
            $className = $nameSpace."\\Setup";
            if (class_exists($className) && method_exists($className, 'build')) {
                $corporation = call_user_func($className."::build");
                if ($corporation) {
                    $corporation->install();               
                    $this->info(json_encode($corporation, true));
                } else {
                    $this->error("Error: Unable to build the Corporation.");
                    return Command::FAILURE;
                }
            } else {
                $this->error("Error: Setup class not found or incorrectly configured.");
                return Command::FAILURE;
            }
            $this->info("Your Corporation has been installed successfully.");
            return Command::SUCCESS;
        }
        else
        {
            $this->error("The installation has failed: No setup file found in $folder.\n");
            return Command::FAILURE;
        }

    }
}