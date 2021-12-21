<?php

namespace App\Models;

use App\Models\Concerns\Unguarded;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

class Member extends MorphPivot
{
    use Unguarded;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stable_members';

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
