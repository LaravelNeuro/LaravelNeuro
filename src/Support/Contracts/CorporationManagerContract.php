<?php

namespace LaravelNeuro\Support\Contracts;

use LaravelNeuro\Networking\Database\Models\NetworkCorporation;
use LaravelNeuro\Networking\Database\Models\NetworkProject;

interface CorporationManagerContract
{
    public function mount(int|string $corporation) : CorporationManagerContract;
    public function task(string $task) : CorporationManagerContract;
    public function run() : NetworkProject;

    public function debug(bool $debug = true) : CorporationManagerContract;
    public function disableHistory() : CorporationManagerContract;
    public function enableHistory() : CorporationManagerContract;

    public function getCorporationModel() : NetworkCorporation;
}
