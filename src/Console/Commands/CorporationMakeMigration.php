<?php

namespace LaravelNeuro\Console\Commands;

use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Support\Composer;
use Illuminate\Console\Command;

/**
 * Signature: lneuro:make-network-migration
 * Provides a command to create a migration file for a new network entity. 
 * 
 * @package LaravelNeuro
 */
class CorporationMakeMigration extends MigrateMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lneuro:make-network-migration {name : The name of the migration.} 
                                                    {--create= : The table to be created.} 
                                                    {--table= : The table to migrate.} 
                                                    {--path= : The location where the migration file should be created.} 
                                                    {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Modifies the naming scheme for MigrateMakeCommand to allow for file creation without dynamic timestamp.';

    public function __construct(MigrationCreator $creator, Composer $composer)
    {
        parent::__construct($creator, $composer);
    }

    protected function writeMigration($name, $table, $create)
    {
        $file = $this->creator->create(
            $name, $this->input->getOption('path'), $table, $create
        );

            $pathSegments = explode('/', $file);
            array_pop($pathSegments);
            $filePath = implode('/', array_merge($pathSegments, [$name.'.php']));
            if(!file_exists($filePath))
            {
                if(!rename($file, $filePath))
                {
                    $this->error(sprintf('Migration [%s] could not be renamed correctly to [%s]. Deleting migration.', ...[$file, $filePath]));
                    unlink($file);
                }
                else 
                {
                    $this->info('Laravel Neuro state-machine migration created successfully.');
                    $this->info(sprintf('Migration name: [%s].', $filePath));
                    return Command::SUCCESS;
                }
            }
            else
            {
                if(!unlink($file))
                {
                    $this->error(sprintf('Migration [%s] already exists, but the temporary migration file created during this operation could not be deleted automatically.', $filePath));
                    return Command::FAILURE;
                }
                else
                {
                    $this->error(sprintf('Migration [%s] already exists.', $filePath));
                    return Command::SUCCESS;
                }
            }
    }
}
