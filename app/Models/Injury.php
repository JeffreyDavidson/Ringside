<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Injury extends Model
{
    use Concerns\Unguarded;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['started_at', 'ended_at'];

    /**
     * Retrieve the injured model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function injurable()
    {
        return $this->morphTo();
    }
}
