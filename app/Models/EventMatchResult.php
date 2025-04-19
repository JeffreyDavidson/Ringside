<?php

declare(strict_types=1);

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $event_match_id
 * @property string $winner_type
 * @property int $winner_id
 * @property int $match_decision_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read MatchDecision $decision
 * @property-read Wrestler|TagTeam $winner
 * @property-read EventMatch $eventMatch
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchResult newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchResult newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchResult query()
 *
 * @mixin \Eloquent
 */
class EventMatchResult extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'events_matches_results';

    /**
     * Get the winner of the event match.
     *
     * @return MorphTo<Model, $this>
     */
    public function winner(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'winner_type', 'winner_id');
    }

    public function getCompetitor(): Wrestler|TagTeam
    {
        $competitor = $this->competitor;

        if (! is_object($competitor)) {
            throw new Exception('No popularized object');
        }

        return match ($competitor::class) {
            Wrestler::class,
            TagTeam::class => $competitor,
            default => throw new Exception('Unexpected relation: '.$competitor::class),
        };
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

    public function getWinner(): Wrestler|TagTeam
    {
        $winner = $this->winner;

        if (! is_object($winner)) {
            throw new Exception('No popularized object');
        }

        return match ($winner::class) {
            Wrestler::class,
            TagTeam::class => $winner,
            default => throw new Exception('Unexpected relation: '.$winner::class),
        };
    }
}
