<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plant extends Model
{
    protected $fillable = [
        'common_name',
        'watering_general_benchmark'
    ];

    protected $casts = [
        'watering_general_benchmark' => 'array'
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
