<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes,
        HasFactory,
        Concerns\Unguarded;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['date'];

    /**
     * Retrieve the venue of the event.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Scope a query to only include scheduled events.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', EventStatus::SCHEDULED);
    }

    /**
     * Scope a query to only include past events.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePast($query)
    {
        return $query->where('status', EventStatus::PAST);
    }

    /**
     * Checks to see if the event is scheduled for a future date.
     *
     * @return bool
     */
    public function isScheduled()
    {
        return $this->date->isFuture();
    }

    /**
     * Checks to see if the event has taken place.
     *
     * @return bool
     */
    public function isPast()
    {
        return $this->date->isPast();
    }

    /**
     * Checks to see if the event is does not have a scheduled date.
     *
     * @return bool
     */
    public function isUnScheduled()
    {
        return $this->date === null;
    }

    public function getFormattedDateAttribute()
    {
        return $this->date->format('F j, Y');
    }
}
