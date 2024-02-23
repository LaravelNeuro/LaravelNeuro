<?php

namespace LaravelNeuro\LaravelNeuro\Networking\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkUnit;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkAgent;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkProject;

use LaravelNeuro\LaravelNeuro\Enums\TuringHistory;

class NetworkHistory extends Model
{
    protected $table = 'laravel_neuro_network_history';
    protected $fillable = ['project_id', 'unit_id', 'agent_id', 'entryType', 'content'];

    public function project() : BelongsTo
    {
        return $this->belongsTo(NetworkProject::class, 'project_id');
    }

    public function unit() : BelongsTo
    {
        return $this->belongsTo(NetworkUnit::class, 'unit_id');
    }

    public function agent() : BelongsTo
    {
        return $this->belongsTo(NetworkAgent::class, 'agent_id');
    }

    protected function entryType(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => (TuringHistory::tryFrom($value) ?? throw new \Exception("There appears to be a mismatch between the Database enum and the PHP enum for NetworkHistory->entryType / TuringHistory, where each NetworkState->entryType should correspond to a TuringHistory case.")),
            set: fn (TuringHistory $value) => $value->value,
        )->shouldCache();
    }
}
