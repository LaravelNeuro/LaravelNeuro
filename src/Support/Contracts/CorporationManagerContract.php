<?php

namespace LaravelNeuro\Support\Contracts;

use LaravelNeuro\Networking\Database\Models\NetworkCorporation;
use LaravelNeuro\Networking\Database\Models\NetworkProject;

/**
 * The Contract for the CorporationManager, defining the required methods.
 *
 * Defines the contract for managing and interacting with LaravelNeuro Corporations.
 * This interface allows mounting a Corporation by its ID or namespace, configuring 
 * tasks, enabling/disabling debugging, toggling history logging, and executing the 
 * Corporation’s state machine.
 *
 * @package LaravelNeuro
 */
interface CorporationManagerContract
{
    /**
     * Mounts a Corporation by its ID or namespace.
     *
     * @param int|string $corporation The Corporation ID or namespace.
     * @return CorporationManagerContract
     *
     * @throws \Exception If the Corporation cannot be found or mounted.
     */
    public function mount(int|string $corporation): CorporationManagerContract;

    /**
     * Assigns a task to the currently mounted Corporation.
     *
     * @param string $task The task description or input.
     * @return CorporationManagerContract
     *
     * @throws \Exception If no Corporation is mounted.
     */
    public function task(string $task): CorporationManagerContract;

    /**
     * Executes the Corporation’s state machine.
     *
     * @return NetworkProject The resulting NetworkProject instance containing the output.
     *
     * @throws \Exception If no Corporation is mounted.
     */
    public function run(): NetworkProject;

    /**
     * Enables or disables debug mode for the mounted Corporation.
     *
     * @param bool $debug Set to true to enable debug mode, false to disable it.
     * @return CorporationManagerContract
     *
     * @throws \Exception If no Corporation is mounted.
     */
    public function debug(bool $debug = true): CorporationManagerContract;

    /**
     * Disables history logging for the current Corporation run.
     *
     * @return CorporationManagerContract
     *
     * @throws \Exception If no Corporation is mounted.
     */
    public function disableHistory(): CorporationManagerContract;

    /**
     * Enables history logging for the current Corporation run.
     *
     * @return CorporationManagerContract
     *
     * @throws \Exception If no Corporation is mounted.
     */
    public function enableHistory(): CorporationManagerContract;

    /**
     * Retrieves the NetworkCorporation model associated with the mounted Corporation.
     *
     * @return NetworkCorporation
     *
     * @throws \Exception If no Corporation is mounted.
     */
    public function getCorporationModel(): NetworkCorporation;
}
