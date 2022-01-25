<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['employable_id', 'employable_type', 'started_at', 'ended_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Get the employed model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function employable()
    {
        return $this->morphTo();
    }

    /**
     * Determine if employment start date was started before a given date.
     *
     * @param  \Carbon\Carbon $date
     *
     * @return bool
     */
    public function startedBefore(Carbon $date)
    {
        return $this->started_at->lte($date);
    }

    /**
     * Determine if employment start date was started after a given date.
     *
     * @param  \Carbon\Carbon $date
     *
     * @return bool
     */
    public function startedAfter(Carbon $date)
    {
        return $this->started_at->gt($date);
    }
}
