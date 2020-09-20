<?php

namespace App\Models;

use App\Models\Concerns\Unguarded;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use Unguarded;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stable_members';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['joined_at', 'left_at'];
}
