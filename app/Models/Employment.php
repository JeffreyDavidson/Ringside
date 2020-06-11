<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employment extends Model
{
    use Concerns\Unguarded;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['started_at', 'ended_at'];

    /**
     * Get the owning employed model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function employable()
    {
        return $this->morphTo();
    }

    /**
     * Retrieve an employment started before a given date.
     *
     * @param  string $date
     * @return boolean
     */
    public function startedBefore($date)
    {
        return $this->started_at->lte($date);
    }
}
