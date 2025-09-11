<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PlantUser extends Pivot
{

    protected $fillable = [
        'city',
    ];

    protected $hidden = [
        'user_id',
        'plant_id',
    ];

    protected $visible = [
        'id',
        'city',
        'created_at',
        'updated_at',
    ];
}