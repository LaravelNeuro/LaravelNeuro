<?php

namespace LaravelNeuro\Networking\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use LaravelNeuro\Networking\Database\Models\NetworkCorporation;
use LaravelNeuro\Networking\Database\Models\NetworkDataSet;
use LaravelNeuro\Networking\Database\Models\NetworkAgent;

use LaravelNeuro\Enums\UnitReceiver;

class NetworkUnit extends Model
{
    protected $table = 'laravel_neuro_network_units';

    public function corporation() : BelongsTo
    {
        return $this->belongsTo(NetworkCorporation::class, 'corporation_id');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(NetworkAgent::class, 'unit_id');
    }

    public function dataSets(): HasMany
    {
        return $this->hasMany(NetworkDataSet::class, 'unit_id');
    }

    public function dataSetTemplates(): HasMany
    {
        return $this->hasMany(NetworkDataSetTemplate::class, 'unit_id');
    }

    protected function defaultReceiverType(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => (UnitReceiver::tryFrom($value) ?? throw new \Exception("There appears to be a mismatch between the Database enum and the PHP enum for NetworkUnit->defaultReceiverType / UnitReceiver, where each NetworkUnit->defaultReceiverType should correspond to a UnitReceiver case.")),
            set: fn (UnitReceiver $value) => $value->value,
        )->shouldCache();
    }
}
