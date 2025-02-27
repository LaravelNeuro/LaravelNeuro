<?php

namespace LaravelNeuro\Networking\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LaravelNeuro\Networking\Database\Models\NetworkCorporation;
use LaravelNeuro\Networking\Database\Models\NetworkDataSet;
use LaravelNeuro\Networking\Database\Models\NetworkHistory;
use LaravelNeuro\Networking\Database\Models\NetworkState;

/**
 * Represents a given LaravelNeuro Corporation run.
 *
 * @package LaravelNeuro
 */
class NetworkProject extends Model
{
    protected $table = 'laravel_neuro_network_projects';

    public function corporation() : BelongsTo
    {
        return $this->belongsTo(NetworkCorporation::class, 'corporation_id');
    }

    public function states(): HasMany
    {
        return $this->hasMany(NetworkState::class, 'project_id');
    }
    
    public function dataSets(): HasMany
    {
        return $this->hasMany(NetworkDataSet::class, 'project_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(NetworkHistory::class, 'project_id');
    }
}
