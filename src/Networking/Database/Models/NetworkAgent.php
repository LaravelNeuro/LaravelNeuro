<?php

namespace LaravelNeuro\LaravelNeuro\Networking\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelNeuro\LaravelNeuro\Networking\Database\Models\NetworkUnit;

use LaravelNeuro\LaravelNeuro\Enums\APIprovider;
use LaravelNeuro\LaravelNeuro\Enums\APItype;

class NetworkAgent extends Model
{
    protected $table = 'laravel_neuro_network_agents';

    protected $attributes = [
        'promptClass' => 'LaravelNeuro\\LaravelNeuro\\Prompts\\SUAprompt',
    ];

    public function unit() : BelongsTo
    {
        return $this->belongsTo(NetworkUnit::class, 'unit_id');
    }

    protected function apiType(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => (APItype::tryFrom($value) ?? throw new \Exception("There appears to be a mismatch between the Database enum and the PHP enum for NetworkAgent->apiType / APItypes, where each NetworkAgent->apiType should correspond to a APItypes case.")),
            set: fn (APItype $value) => $value->value,
        )->shouldCache();
    }

    protected function apiProvider(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => (APIprovider::tryFrom($value) ?? throw new \Exception("There appears to be a mismatch between the Database enum and the PHP enum for NetworkAgent->apiProvider / APIprovider, where each NetworkAgent->apiProvider should correspond to a APIprovider case.")),
            set: fn (APIprovider $value) => $value->value,
        )->shouldCache();
    }
}
