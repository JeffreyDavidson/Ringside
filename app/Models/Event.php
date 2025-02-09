<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\EventBuilder;
use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\HasBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $date
 * @property int|null $venue_id
 * @property string|null $preview
 * @property \App\Enums\EventStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\TFactory|null $use_factory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventMatch> $matches
 * @property-read \App\Models\Venue|null $venue
 *
 * @method static \Database\Factories\EventFactory factory($count = null, $state = [])
 * @method static EventBuilder<static>|Event newModelQuery()
 * @method static EventBuilder<static>|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event onlyTrashed()
 * @method static EventBuilder<static>|Event past()
 * @method static EventBuilder<static>|Event query()
 * @method static EventBuilder<static>|Event scheduled()
 * @method static EventBuilder<static>|Event unscheduled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Event extends Model
{
    /** @use HasBuilder<EventBuilder<static>> */
    use HasBuilder;

    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'date',
        'venue_id',
        'preview',
        'status',
    ];

    protected static string $builder = EventBuilder::class;

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
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => EventStatus::Unscheduled->value,
    ];

    /**
     * Retrieve the venue of the event.
     *
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Retrieve the matches for the event.
     *
     * @return HasMany<EventMatch, $this>
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
        return $this->date !== null;
    }

    /**
     * Checks to see if the event is unscheduled.
     */
    public function isUnscheduled(): bool
    {
        return $this->date === null;
    }

    /**
     * Checks to see if the event is scheduled for a future date.
     */
    public function hasFutureDate(): bool
    {
        return $this->isScheduled() && $this->date?->isFuture();
    }

    /**
     * Checks to see if the event has already taken place.
     */
    public function hasPastDate(): bool
    {
        return $this->isScheduled() && $this->date?->isPast();
    }
}
