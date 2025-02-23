<?php

namespace LaravelNeuro\Networking\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LaravelNeuro\Networking\Database\Models\NetworkUnit;

class NetworkDataSetTemplate extends Model
{
    protected $table = 'laravel_neuro_network_dataset_templates';
    protected $fillable = ['template', 'unit_id', 'name', 'completionPrompt', 'completionResponse'];

    public function unit() : BelongsTo
    {
        return $this->belongsTo(NetworkUnit::class, 'unit_id');
    }

    public function dataSets(): HasMany
    {
        return $this->hasMany(NetworkDataSet::class, 'template_id');
    }
}
