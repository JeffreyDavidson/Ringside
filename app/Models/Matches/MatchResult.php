<?php

declare(strict_types=1);

namespace App\Models\Matches;

use App\Enums\MatchDecision;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Matches\MatchResultFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $match_id
 * @property MatchDecision $match_decision
 * @property string $winner_type
 * @property int $winner_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read EventMatch $match
 * @property-read Collection<int, MatchWinner> $winners
 * @property-read Collection<int, MatchLoser> $losers
 * @property-read Wrestler|TagTeam $winner
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchResult newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchResult newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchResult query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(MatchResultFactory::class)]
class MatchResult extends Model
{
    /** @use HasFactory<MatchResultFactory> */
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
        'match_id',
        'match_decision',
        'winner_type',
        'winner_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'match_decision' => MatchDecision::class,
        ];
    }

    /**
     * Get the event match this result belongs to.
     *
     * @return BelongsTo<EventMatch, $this>
     */
    public function match(): BelongsTo
    {
        return $this->belongsTo(EventMatch::class);
    }

    /**
     * Get all winners of the event match.
     *
     * @return HasMany<MatchWinner, $this>
     */
    public function winners(): HasMany
    {
        return $this->hasMany(MatchWinner::class);
    }

    /**
     * Get all losers of the event match.
     *
     * @return HasMany<MatchLoser, $this>
     */
    public function losers(): HasMany
    {
        return $this->hasMany(MatchLoser::class);
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
}
