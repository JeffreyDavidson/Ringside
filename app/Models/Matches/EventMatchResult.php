<?php

declare(strict_types=1);

namespace App\Models\Matches;

use Database\Factories\Matches\EventMatchResultFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_match_id
 * @property int $match_decision_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MatchDecision $decision
 * @property-read EventMatch $eventMatch
 * @property-read Collection<int, EventMatchWinner> $winners
 * @property-read Collection<int, EventMatchLoser> $losers
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchResult newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchResult newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchResult query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(EventMatchResultFactory::class)]
class EventMatchResult extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'events_matches_results';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_match_id',
        'match_decision_id',
    ];

    /**
     * Get the event match this result belongs to.
     *
     * @return BelongsTo<EventMatch, $this>
     */
    public function eventMatch(): BelongsTo
    {
        return $this->belongsTo(EventMatch::class);
    }

    /**
     * Get the decision of the end of the event match.
     *
     * @return BelongsTo<MatchDecision, $this>
     */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(MatchDecision::class, 'match_decision_id');
    }

    /**
     * Get all winners of the event match.
     *
     * @return HasMany<EventMatchWinner, $this>
     */
    public function winners(): HasMany
    {
        return $this->hasMany(EventMatchWinner::class);
    }

    /**
     * Get all losers of the event match.
     *
     * @return HasMany<EventMatchLoser, $this>
     */
    public function losers(): HasMany
    {
        return $this->hasMany(EventMatchLoser::class);
    }
}
