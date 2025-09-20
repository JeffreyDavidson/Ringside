<?php

declare(strict_types=1);

namespace App\Models\Matches;

use App\Collections\MatchCompetitorsCollection;
use App\Enums\MatchType;
use App\Models\Events\Event;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Matches\MatchFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property int $match_number
 * @property MatchType $match_type
 * @property int|null $match_stipulation_id
 * @property string|null $preview
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read MatchCompetitor|null $pivot
 * @property-read MatchCompetitorsCollection<int, MatchCompetitor> $competitors
 * @property-read Event $event
 * @property-read MatchStipulation|null $matchStipulation
 * @property-read MatchResult|null $result
 * @property-read Collection<int, Referee> $referees
 * @property-read Collection<int, TagTeam> $tagTeams
 * @property-read Collection<int, Title> $titles
 * @property-read Collection<int, MatchWinner> $winners
 * @property-read Collection<int, MatchLoser> $losers
 * @property-read Collection<int, Wrestler> $wrestlers
 *
 * @method static \Database\Factories\Matches\MatchFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatch query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(MatchFactory::class)]
class EventMatch extends Model
{
    /** @use HasFactory<MatchFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'events_matches';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'match_number',
        'match_type',
        'match_stipulation_id',
        'preview',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'match_type' => MatchType::class,
        ];
    }

    /**
     * Get the event the match belongs to.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the match stipulation of the match.
     *
     * @return BelongsTo<MatchStipulation, $this>
     */
    public function matchStipulation(): BelongsTo
    {
        return $this->belongsTo(MatchStipulation::class, 'match_stipulation_id');
    }

    /**
     * Get the referees assigned to the match.
     *
     * @return BelongsToMany<Referee, $this>
     */
    public function referees(): BelongsToMany
    {
        return $this->belongsToMany(Referee::class, 'events_matches_referees', 'match_id');
    }

    /**
     * Get the titles being competed for in the match.
     *
     * @return BelongsToMany<Title, $this>
     */
    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(Title::class, 'events_matches_titles', 'match_id');
    }

    /**
     * Get all the event match competitors for the match.
     *
     * @return HasMany<MatchCompetitor, $this>
     */
    public function competitors(): HasMany
    {
        return $this->hasMany(MatchCompetitor::class, 'match_id');
    }

    /**
     * Get the wrestlers involved in the match.
     *
     * @return MorphToMany<Wrestler, $this, MatchCompetitor>
     */
    public function wrestlers(): MorphToMany
    {
        return $this->morphedByMany(Wrestler::class, 'competitor', 'events_matches_competitors', 'match_id')
            ->using(MatchCompetitor::class)
            ->withPivot('side_number');
    }

    /**
     * Get the tag teams involved in the match.
     *
     * @return MorphToMany<TagTeam, $this, MatchCompetitor>
     */
    public function tagTeams(): MorphToMany
    {
        return $this->morphedByMany(TagTeam::class, 'competitor', 'events_matches_competitors', 'match_id')
            ->using(MatchCompetitor::class)
            ->withPivot('side_number');
    }

    /**
     * Get the result of the match.
     *
     * @return HasOne<MatchResult, $this>
     */
    public function result(): HasOne
    {
        return $this->hasOne(MatchResult::class, 'match_id');
    }

    /**
     * Get all winners of the match through the result.
     *
     * @return HasManyThrough<MatchWinner, MatchResult, $this>
     */
    public function winners(): HasManyThrough
    {
        return $this->hasManyThrough(MatchWinner::class, MatchResult::class);
    }

    /**
     * Get all losers of the match through the result.
     *
     * @return HasManyThrough<MatchLoser, MatchResult, $this>
     */
    public function losers(): HasManyThrough
    {
        return $this->hasManyThrough(MatchLoser::class, MatchResult::class);
    }
}
