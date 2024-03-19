<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\EventBuilder;
use App\Enums\EventStatus;
use App\Observers\EventObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([EventObserver::class])]
class Event extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'date',
        'venue_id',
        'preview',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'status' => EventStatus::class,
        ];
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @return EventBuilder<Event>
     */
    public function newEloquentBuilder($query): EventBuilder // @pest-ignore-type
    {
        return new EventBuilder($query);
    }

    /**
     * Retrieve the venue of the event.
     *
     * @return BelongsTo<Venue, Event>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Retrieve the matches for the event.
     *
     * @return HasMany<EventMatch>
     */
    public function matches(): HasMany
    {
        return $this->hasMany(EventMatch::class);
    }

    /**
     * Checks to see if the event is scheduled for a future date.
     */
    public function isScheduled(): bool
    {
        if (is_null($this->date)) {
            return false;
        }

        return $this->date->isFuture();
    }

    /**
     * Checks to see if the event has already taken place.
     */
    public function isPast(): bool
    {
        if (is_null($this->date)) {
            return false;
        }

        return $this->date->isPast();
    }

    /**
     * Checks to see if the event is unscheduled.
     */
    public function isUnscheduled(): bool
    {
        return $this->date === null;
    }
}
