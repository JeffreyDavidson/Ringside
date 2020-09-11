<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Retirement extends Model
{
    use HasFactory,
        Concerns\Unguarded;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['started_at', 'ended_at'];

    /**
     * Retrieve the retired model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function retiree()
    {
        return $this->morphTo();
    }
}
