<?php

namespace LaravelNeuro\LaravelNeuro\Console\Commands;

use Illuminate\Console\Command;

class PackageSetup extends Command
{
    protected $signature = 'lneuro:migrate {--rollback : Rollback the package migrations} 
                                           {--v : verbose migration}';
    protected $description = 'Run the necessary migrations for Laravel Neuro\'s Agent Network Functionality.';

    public function handle()
    {
        $path = __DIR__.'/../../Networking/Database/migrations';
        $arguments = ["--path" => $path];
        if($this->option('v')) $arguments[] = '-v';

        if ($this->option('rollback')) {
            $this->rollbackMigrations($arguments);
        } else {
            $this->runMigrations($arguments);
        }
    }

    protected function runMigrations($arguments)
    {
        $this->call('migrate', $arguments);
    }

    protected function rollbackMigrations($arguments)
    {
        $this->call('migrate:rollback', $arguments);
    }
}