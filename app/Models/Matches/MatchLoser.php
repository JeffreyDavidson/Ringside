<?php

declare(strict_types=1);

namespace App\Models\Matches;

use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Matches\MatchLoserFactory;
use Exception;
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
 * @property-read Wrestler|TagTeam $loser
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

    /**
     * Get the loser entity through the competitor relationship.
     */
    public function loser(): Wrestler|TagTeam
    {
        return $this->competitor->competitor;
    }

    /**
     * Get the loser entity with type safety.
     *
     * @throws Exception
     */
    public function getLoser(): Wrestler|TagTeam
    {
        $loser = $this->loser();

        return match ($loser::class) {
            Wrestler::class,
            TagTeam::class => $loser,
            default => throw new Exception('Unexpected loser type: '.$loser::class),
        };
    }

    /**
     * Get loser type for backward compatibility.
     */
    public function getLoserTypeAttribute(): string
    {
        return $this->competitor->competitor_type;
    }

    /**
     * Get loser ID for backward compatibility.
     */
    public function getLoserIdAttribute(): int
    {
        return $this->competitor->competitor_id;
    }
}
