<?php

declare(strict_types=1);

namespace App\Models\Matches;

use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Matches\EventMatchWinnerFactory;
use Exception;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Event match winner model for tracking match winners.
 *
 * This model represents individual winners of a match, allowing
 * multiple winners per match result (e.g., tag team partners,
 * handicap match winners). Associated with EventMatchResult to
 * maintain connection between decision and winners.
 *
 * @property int $id
 * @property int $event_match_result_id
 * @property string $winner_type
 * @property int $winner_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EventMatchResult $eventMatchResult
 * @property-read Wrestler|TagTeam $winner
 *
 * @method static \Database\Factories\Matches\EventMatchWinnerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchWinner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchWinner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchWinner query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(EventMatchWinnerFactory::class)]
class EventMatchWinner extends Model
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
        'event_match_result_id',
        'winner_type',
        'winner_id',
    ];

    /**
     * Get the event match result that owns this winner record.
     *
     * @return BelongsTo<EventMatchResult, $this>
     */
    public function eventMatchResult(): BelongsTo
    {
        return $this->belongsTo(EventMatchResult::class);
    }

    /**
     * Get the winner of the event match.
     *
     * @return MorphTo<Model, $this>
     */
    public function winner(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'winner_type', 'winner_id');
    }

    /**
     * Get the winner entity with type safety.
     *
     * @return Wrestler|TagTeam
     * @throws Exception
     */
    public function getWinner(): Wrestler|TagTeam
    {
        $winner = $this->winner;

        return match ($winner::class) {
            Wrestler::class,
            TagTeam::class => $winner,
            default => throw new Exception('Unexpected winner type: '.$winner::class),
        };
    }
}