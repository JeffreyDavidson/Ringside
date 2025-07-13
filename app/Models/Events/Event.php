<?php

declare(strict_types=1);

namespace App\Models\Events;

use App\Builders\Events\EventBuilder;
use App\Models\Concerns\HasEventMatches;
use App\Models\Matches\EventMatch;
use App\Models\Shared\Venue;
use Database\Factories\Events\EventFactory;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $date
 * @property int|null $venue_id
 * @property string|null $preview
 * @property string|null $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Venue|null $venue
 * @property-read Collection<int, EventMatch> $matches
 *
 * @method static \Database\Factories\Events\EventFactory factory($count = null, $state = [])
 * @method static EventBuilder<static>|Event newModelQuery()
 * @method static EventBuilder<static>|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event onlyTrashed()
 * @method static EventBuilder<static>|Event past()
 * @method static EventBuilder<static>|Event query()
 * @method static EventBuilder<static>|Event scheduled()
 * @method static EventBuilder<static>|Event unscheduled()
 * @method static EventBuilder<static>|Event withFutureDate()
 * @method static EventBuilder<static>|Event withPastDate()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[UseFactory(EventFactory::class)]
#[UseEloquentBuilder(EventBuilder::class)]
class Event extends Model
{
    use HasEventMatches;
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
        ];
    }

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
