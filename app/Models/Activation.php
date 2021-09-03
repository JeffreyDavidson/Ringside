<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activation extends Model
{
    use Concerns\Unguarded,
        HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'activations';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['started_at', 'ended_at'];

    /**
     * Get the owning activated model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function activatable()
    {
        return $this->morphTo();
    }

    /**
     * Retrieve an activation started before a given date.
     *
     * @param  string $date
     * @return bool
     */
    public function startedBefore($date)
    {
        return $this->started_at->lt($date);
    }
}
