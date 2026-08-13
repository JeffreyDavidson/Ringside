<?php

declare(strict_types=1);

namespace App\Models\Matches;

use Database\Factories\Matches\MatchLoserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Event match loser model for tracking match losers.
 *
 * This model represents individual losers of a match, providing
 * symmetric querying capabilities alongside MatchWinner.
 * Associated with MatchResult to maintain connection between
 * decision and match outcomes.
 *
 * @property int $id
 * @property int $match_result_id
 * @property int $match_competitor_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read MatchResult $matchResult
 * @property-read MatchCompetitor $competitor
 *
 * @method static \Database\Factories\Matches\MatchLoserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchLoser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchLoser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchLoser query()
 *
 * @mixin \Eloquent
 */
#[Table('events_matches_losers')]
#[Fillable('match_result_id', 'match_competitor_id')]
#[UseFactory(MatchLoserFactory::class)]
class MatchLoser extends Model
{
    /** @use HasFactory<MatchLoserFactory> */
    use HasFactory;

    /**
     * Get the event match result that owns this loser record.
     *
     * @return BelongsTo<MatchResult, $this>
     */
    public function matchResult(): BelongsTo
    {
        return $this->belongsTo(MatchResult::class);
    }

    /**
     * Get the match competitor that lost.
     *
     * @return BelongsTo<MatchCompetitor, $this>
     */
    public function competitor(): BelongsTo
    {
        return $this->belongsTo(MatchCompetitor::class, 'match_competitor_id');
    }
}
