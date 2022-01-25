<?php

namespace App\Models;

use App\Builders\EventQueryBuilder;
use App\Observers\EventObserver;
use App\Presenters\EventPresenter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['name', 'date', 'venue_id', 'preview', 'status'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'datetime',
    ];

    /**
     * The "boot" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        self::observe(EventObserver::class);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return \App\Builders\EventQueryBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new EventQueryBuilder($query);
    }

    /**
     * Present the event model.
     *
     * @return \App\Presenters\EventPresenter
     */
    public function present()
    {
        return new EventPresenter($this);
    }

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
     * Retrieve the matches for the event.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function matches()
    {
        return $this->hasMany(EventMatch::class);
    }

    /**
     * Checks to see if the event is scheduled for a future date.
     *
     * @return bool
     */
    public function isScheduled()
    {
        return $this->date?->isFuture() ?? false;
    }

    /**
     * Checks to see if the event has already taken place.
     *
     * @return bool
     */
    public function isPast()
    {
        return $this->date?->isPast() ?? false;
    }

    /**
     * Checks to see if the event is unscheduled.
     *
     * @return bool
     */
    public function isUnscheduled()
    {
        return $this->date === null;
    }
}
