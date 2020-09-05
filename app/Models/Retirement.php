<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

class Retirement extends MorphPivot
{
    use Concerns\Unguarded;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'retirements';

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
