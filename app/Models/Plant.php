<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plant extends Model
{
    protected $fillable = [
        'common_name',
        'watering_general_benchmark',
        'api_id',
        'watering',
        'watering_period',
        'flowers',
        'fruits',
        'leaf',
        'growth_rate',
        'maintenance'
    ];

    protected $casts = [
        'watering_general_benchmark' => 'array',
        'flowers' => 'boolean',
        'fruits' => 'boolean',
        'leaf' => 'boolean'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
