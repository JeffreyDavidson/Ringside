<?php

declare(strict_types=1);

namespace App\Models\Matches;

use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Matches\EventMatchLoserFactory;
use Exception;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Event match loser model for tracking match losers.
 *
 * This model represents individual losers of a match, providing
 * symmetric querying capabilities alongside EventMatchWinner.
 * Associated with EventMatchResult to maintain connection between
 * decision and match outcomes.
 *
 * @property int $id
 * @property int $event_match_result_id
 * @property string $loser_type
 * @property int $loser_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EventMatchResult $eventMatchResult
 * @property-read Wrestler|TagTeam $loser
 *
 * @method static \Database\Factories\Matches\EventMatchLoserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchLoser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchLoser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchLoser query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(EventMatchLoserFactory::class)]
class EventMatchLoser extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'events_matches_losers';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_match_result_id',
        'loser_type',
        'loser_id',
    ];

    /**
     * Get the event match result that owns this loser record.
     *
     * @return BelongsTo<EventMatchResult, $this>
     */
    public function eventMatchResult(): BelongsTo
    {
        return $this->belongsTo(EventMatchResult::class);
    }

    /**
     * Get the loser of the event match.
     *
     * @return MorphTo<Model, $this>
     */
    public function loser(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'loser_type', 'loser_id');
    }

    /**
     * Get the loser entity with type safety.
     *
     * @return Wrestler|TagTeam
     * @throws Exception
     */
    public function getLoser(): Wrestler|TagTeam
    {
        $loser = $this->loser;

        return match ($loser::class) {
            Wrestler::class,
            TagTeam::class => $loser,
            default => throw new Exception('Unexpected loser type: '.$loser::class),
        };
    }
}
