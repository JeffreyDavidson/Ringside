<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['activatable_id', 'activatable_type', 'started_at', 'ended_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Get the activated model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function activatable()
    {
        return $this->morphTo();
    }

    /**
     * Determine an activation started before a given date.
     *
     * @param  \Illuminate\Support\Carbon $date
     * @return bool
     */
    public function startedBefore(Carbon $date)
    {
        return $this->started_at->lt($date);
    }
}
