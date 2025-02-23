<?php

namespace LaravelNeuro\Networking\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelNeuro\Networking\Database\Models\NetworkDataSetTemplate;
use LaravelNeuro\Networking\Database\Models\NetworkProject;

class NetworkDataSet extends Model
{
    protected $table = 'laravel_neuro_network_datasets';
    protected $fillable = ['template', 'project_id', 'template_id', 'data'];

    public function project() : BelongsTo
    {
        return $this->belongsTo(NetworkProject::class, 'project_id');
    }

    public function template() : BelongsTo
    {
        return $this->belongsTo(NetworkDataSetTemplate::class, 'template_id');
    }
}
