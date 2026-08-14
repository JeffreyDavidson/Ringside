<?php

declare(strict_types=1);

namespace App\Models\Events;

use App\Builders\Events\EventBuilder;
use App\Enums\EventStatus;
use App\Models\Concerns\HasLifecycleTransitions;
use App\Models\Contracts\SoftDeletable;
use App\Models\Matches\EventMatch;
use Database\Factories\Events\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $date
 * @property int|null $venue_id
 * @property string|null $preview
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read EventStatus $status
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable('name', 'date', 'venue_id', 'preview')]
#[Appends('status')]
#[UseFactory(EventFactory::class)]
#[UseEloquentBuilder(EventBuilder::class)]
class Event extends Model implements SoftDeletable
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use HasLifecycleTransitions;
    use SoftDeletes;

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

    /** @return HasMany<EventMatch, $this> */
    public function matches(): HasMany
    {
        return $this->hasMany(EventMatch::class);
    }

    /**
     * Get the computed status of the event based on its date.
     *
     * @return Attribute<EventStatus, never>
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn (): EventStatus => EventStatus::fromDate($this->date)
        );
    }
}
