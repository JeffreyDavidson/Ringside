<?php

declare(strict_types=1);

namespace App\Models\Matches;

use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Matches\MatchWinnerFactory;
use Exception;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Match winner model for tracking match winners.
 *
 * This model represents individual winners of a match, allowing
 * multiple winners per match result (e.g., tag team partners,
 * handicap match winners). Associated with MatchResult to
 * maintain connection between decision and winners.
 *
 * @property int $id
 * @property int $match_result_id
 * @property int $match_competitor_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MatchResult $matchResult
 * @property-read MatchCompetitor $competitor
 * @property-read Wrestler|TagTeam $winner
 *
 * @method static \Database\Factories\Matches\MatchWinnerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchWinner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchWinner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchWinner query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(MatchWinnerFactory::class)]
class MatchWinner extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'events_matches_winners';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'match_result_id',
        'match_competitor_id',
    ];

    /**
     * Get the event match result that owns this winner record.
     *
     * @return BelongsTo<MatchResult, $this>
     */
    public function matchResult(): BelongsTo
    {
        return $this->belongsTo(MatchResult::class);
    }

    /**
     * Get the match competitor that won.
     *
     * @return BelongsTo<MatchCompetitor, $this>
     */
    public function competitor(): BelongsTo
    {
        return $this->belongsTo(MatchCompetitor::class, 'match_competitor_id');
    }

    /**
     * Get the winner entity through the competitor relationship.
     *
     * @return Wrestler|TagTeam
     */
    public function winner(): Wrestler|TagTeam
    {
        return $this->competitor->competitor;
    }

    /**
     * Get the winner entity with type safety.
     *
     * @throws Exception
     */
    public function getWinner(): Wrestler|TagTeam
    {
        $winner = $this->winner();

        return match ($winner::class) {
            Wrestler::class,
            TagTeam::class => $winner,
            default => throw new Exception('Unexpected winner type: '.$winner::class),
        };
    }

    /**
     * Get winner type for backward compatibility.
     */
    public function getWinnerTypeAttribute(): string
    {
        return $this->competitor->competitor_type;
    }

    /**
     * Get winner ID for backward compatibility.
     */
    public function getWinnerIdAttribute(): int
    {
        return $this->competitor->competitor_id;
    }
}
