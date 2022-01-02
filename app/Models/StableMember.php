<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

class StableMember extends MorphPivot
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['stable_id', 'member_id', 'member_type', 'joined_at', 'left_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];
}
