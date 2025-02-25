<?php

namespace LaravelNeuro\Networking\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LaravelNeuro\Networking\Database\Models\NetworkProject;
use LaravelNeuro\Networking\Database\Models\NetworkUnit;

class NetworkCorporation extends Model
{
    protected $table = 'laravel_neuro_network_corporations';

    public function projects(): HasMany
    {
        return $this->hasMany(NetworkProject::class, 'corporation_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(NetworkUnit::class, 'corporation_id');
    }
}
