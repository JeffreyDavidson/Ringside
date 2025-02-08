<?php

declare(strict_types=1);

namespace App\Models;

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
 * @property-read \App\Models\MatchDecision|null $decision
 * @property-read \Illuminate\Database\Eloquent\Model $winner
 * @property-read \App\Models\EventMatch $eventMatch
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
        return $this->morphTo();
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
}
