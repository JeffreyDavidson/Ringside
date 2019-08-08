<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

class Member extends MorphPivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'members';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['joined_at', 'left_at'];
}
