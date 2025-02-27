<?php

namespace LaravelNeuro\Networking\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelNeuro\Networking\Database\Models\NetworkProject;

use LaravelNeuro\Enums\TuringState;

/**
 * A set of NetworkState models are created for a running Corporation to represent
 * the Corporation's Transitions, effectively creating a "Turing Machine memory strip"
 * for the state machine to move along.
 *
 * @package LaravelNeuro
 */
class NetworkState extends Model
{
    protected $table = 'laravel_neuro_network_state_machines';
    protected $fillable = ['type', 'active', 'project_id', 'data'];

    public function project() : BelongsTo
    {
        return $this->belongsTo(NetworkProject::class, 'project_id');
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => (TuringState::tryFrom($value) ?? throw new \Exception("There appears to be a mismatch between the Database enum and the PHP enum for NetworkState->type / TuringState, where each NetworkState->type should correspond to a TuringState case.")),
            set: fn (TuringState $value) => $value->value,
        )->shouldCache();
    }
}
