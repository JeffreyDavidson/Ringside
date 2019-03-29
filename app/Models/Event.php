<?php

namespace App\Models;

use App\Traits\Sluggable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use Sluggable,
        SoftDeletes;

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
     * Scope a query to only include archived events.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
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
     * Scope a query to only include past events that are not archived.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePast($query)
    {
        return $query->where('date', '<', now())->whereNull('archived_at');
    }

    /**
     * Scope a query to only include events of a given state.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasState($query, $state)
    {
        $scope = 'scope' . Str::studly($state);

        if (method_exists($this, $scope)) {
            return $this->{$scope}($query);
        }
    }

    /**
     * Checks to see if the event is scheduled.
     *
     * @return boolean
     */
    public function isScheduled()
    {
        return $this->date->isFuture();
    }

    /**
     * Checks to see if the event has past.
     *
     * @return boolean
     */
    public function isPast()
    {
        return $this->date->isPast();
    }

    /**
     * Checks to see if the event has been archived.
     *
     * @return boolean
     */
    public function isArchived()
    {
        return $this->archived_at !== null;
    }

    /**
     * Archives a past event.
     *
     * @return $this
     */
    public function archive()
    {
        $this->update(['archived_at' => now()]);

        return $this;
    }
}
