<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

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
     * Determine the status of the event.
     *
     * @return \App\Enum\EventStatus
     *
     */
    public function getStatusAttribute()
    {
        if ($this->is_scheduled) {
            return EventStatus::SCHEDULED();
        }

        return EventStatus::PAST();
    }

    /**
     * Scope a query to only include scheduled events.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScheduled($query)
    {
        return $query->where('date', '>', now());
    }

    /**
     * Scope a query to only include past events.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePast($query)
    {
        return $query->where('date', '<', now());
    }

    /**
     * Checks to see if the event is scheduled for a future date.
     *
     * @return boolean
     */
    public function getIsScheduledAttribute()
    {
        return $this->date->isFuture();
    }

    /**
     * Checks to see if the event has past.
     *
     * @return boolean
     */
    public function getIsPastAttribute()
    {
        return $this->date->isPast();
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $data                 = parent::toArray();
        $data['status']       = $this->status->label();

        return $data;
    }
}
