<?php

namespace LaravelNeuro\Support\Managers;

use LaravelNeuro\Support\Contracts\CorporationManagerContract;
use LaravelNeuro\Networking\Database\Models\NetworkCorporation;
use LaravelNeuro\Networking\Database\Models\NetworkProject;
use Illuminate\Support\Str;
use Exception;

/**
 * The CoporationManager provides the methods exposed by the Corporation Facade.
 *
 * Provides a mount method to mount a Corporation by its ID or namespace, configuring
 * tasks, enabling/disabling debugging, toggling history logging, and executing the
 * Corporation’s state machine.
 *
 * @package LaravelNeuro
 */
class CorporationManager implements CorporationManagerContract
{
    /**
     * The injected Corporation instance available via the Corporation Facade.
     * 
     * @var \LaravelNeuro\Networking\Corporation
     */
    protected $corporationInstance;

    /**
     * The injected NetworkCorporation instance available via the Corporation Facade.
     * 
     * @var NetworkCorporation
     */
    protected NetworkCorporation $corporationModel;

    /**
     * Mounts a corporation instance using either an ID or a Namespace.
     *
     * @param int|string $corporation
     * @return CorporationManagerContract
     * @throws Exception
     */
    public function mount(int|string $corporation): CorporationManagerContract
    {
        if(is_int($corporation)) {
            // Fetch Corporation by ID
            $this->corporationModel = NetworkCorporation::find($corporation);
        } 
        elseif(is_string($corporation)) 
        {
            // Convert namespace to a proper class path
            $namespace = Str::contains($corporation, '\\') ? $corporation : 'App\\Corporations\\' . $corporation;
            
            // Convert to path to locate Config.php
            $path = base_path(str_replace('App', 'app', str_replace('\\', '/', $namespace)) . '/Config.php');

            if (!file_exists($path)) {
                throw new Exception("Config file not found for Corporation: $namespace");
            }

            // Load Config class dynamically
            require_once $path;
            $configClass = $namespace . '\\Config';

            if (!class_exists($configClass) || !defined("$configClass::CORPORATION")) {
                throw new Exception("Invalid Config file structure: Missing CORPORATION constant.");
            }

            // Retrieve Corporation ID from Config
            $corporationId = $configClass::CORPORATION;

            // Fetch exact NetworkCorporation model
            $this->corporationModel = NetworkCorporation::find($corporationId);

            if (!$this->corporationModel) {
                throw new Exception("Corporation ID ($corporationId) from Config does not exist in the database.");
            }
        }
        else
        {
            throw new Exception("Invalid input type for corporation. Expected int or string.");
        }

        if (!$this->corporationModel) {
            throw new Exception("Corporation not found.");
        }

        // Resolve Namespace and instantiate Corporation
        $namespace = $this->corporationModel->nameSpace;
        if (!Str::startsWith($namespace, 'App\\')) {
            $namespace = 'App\\Corporations\\' . $namespace . '\\' . $namespace;
        }

        if (!class_exists($namespace)) {
            throw new Exception("Corporation class not found in namespace: $namespace");
        }

        // Instantiate the Corporation dynamically with default values
        $this->corporationInstance = new $namespace(task: "");

        return $this;
    }

    /**
     * Sets the task for the mounted corporation.
     *
     * @param string $task
     * @return CorporationManagerContract
     */
    public function task(string $task): CorporationManagerContract
    {
        if (!$this->corporationInstance) {
            throw new Exception("No Corporation mounted. Call mount() first.");
        }
        $this->corporationInstance->task = $task;
        $this->corporationInstance->project->task = $task;
        $this->corporationInstance->project->save();
        return $this;
    }

    /**
     * Runs the mounted corporation and returns the resulting NetworkProject.
     *
     * @return NetworkProject
     */
    public function run(): NetworkProject
    {
        if (!$this->corporationInstance) {
            throw new Exception("No Corporation mounted. Call mount() first.");
        }

        return $this->corporationInstance->run();
    }

    /**
     * Enables or disables debug mode on the corporation.
     *
     * @param bool $debug
     * @return CorporationManagerContract
     */
    public function debug(bool $debug = true): CorporationManagerContract
    {
        if (!$this->corporationInstance) {
            throw new Exception("No Corporation mounted. Call mount() first.");
        }
        $this->corporationInstance->debug = $debug;
        return $this;
    }

    /**
     * Disables history logging for the corporation.
     *
     * @return CorporationManagerContract
     */
    public function disableHistory(): CorporationManagerContract
    {
        if (!$this->corporationInstance) {
            throw new Exception("No Corporation mounted. Call mount() first.");
        }
        $this->corporationInstance->saveHistory = false;
        return $this;
    }

    /**
     * Enables history logging for the corporation.
     *
     * @return CorporationManagerContract
     */
    public function enableHistory(): CorporationManagerContract
    {
        if (!$this->corporationInstance) {
            throw new Exception("No Corporation mounted. Call mount() first.");
        }
        $this->corporationInstance->saveHistory = true;
        return $this;
    }

    /**
     * Retrieves the NetworkCorporation model of the mounted corporation.
     *
     * @return NetworkCorporation
     */
    public function getCorporationModel(): NetworkCorporation
    {
        if (!$this->corporationModel) {
            throw new Exception("No Corporation mounted. Call mount() first.");
        }
        return $this->corporationModel;
    }
}