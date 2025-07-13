<?php

declare(strict_types=1);

namespace App\Models\Matches;

use App\Collections\EventMatchCompetitorsCollection;
use App\Models\Events\Event;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Matches\EventMatchFactory;
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
 * @property int $match_type_id
 * @property int|null $match_stipulation_id
 * @property string|null $preview
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EventMatchCompetitor|null $pivot
 * @property-read EventMatchCompetitorsCollection<int, EventMatchCompetitor> $competitors
 * @property-read Event $event
 * @property-read MatchType|null $matchType
 * @property-read MatchStipulation|null $matchStipulation
 * @property-read EventMatchResult|null $result
 * @property-read Collection<int, Referee> $referees
 * @property-read Collection<int, TagTeam> $tagTeams
 * @property-read Collection<int, Title> $titles
 * @property-read Collection<int, EventMatchWinner> $winners
 * @property-read Collection<int, EventMatchLoser> $losers
 * @property-read Collection<int, Wrestler> $wrestlers
 *
 * @method static \Database\Factories\Matches\EventMatchFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatch query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(EventMatchFactory::class)]
class EventMatch extends Model
{
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
        'match_type_id',
        'match_stipulation_id',
        'preview',
    ];

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
     * Get the match type of the match.
     *
     * @return BelongsTo<MatchType, $this>
     */
    public function matchType(): BelongsTo
    {
        return $this->belongsTo(MatchType::class);
    }

    /**
     * Get the match stipulation of the match.
     *
     * @return BelongsTo<MatchStipulation, $this>
     */
    public function matchStipulation(): BelongsTo
    {
        return $this->belongsTo(MatchStipulation::class);
    }

    /**
     * Get the referees assigned to the match.
     *
     * @return BelongsToMany<Referee, $this>
     */
    public function referees(): BelongsToMany
    {
        return $this->belongsToMany(Referee::class, 'events_matches_referees');
    }

    /**
     * Get the titles being competed for in the match.
     *
     * @return BelongsToMany<Title, $this>
     */
    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(Title::class, 'events_matches_titles');
    }

    /**
     * Get all the event match competitors for the match.
     *
     * @return HasMany<EventMatchCompetitor, $this>
     */
    public function competitors(): HasMany
    {
        return $this->hasMany(EventMatchCompetitor::class);
    }

    /**
     * Get the wrestlers involved in the match.
     *
     * @return MorphToMany<Wrestler, $this, EventMatchCompetitor>
     */
    public function wrestlers(): MorphToMany
    {
        return $this->morphedByMany(Wrestler::class, 'competitor', 'events_matches_competitors')
            ->using(EventMatchCompetitor::class)
            ->withPivot('side_number');
    }

    /**
     * Get the tag teams involved in the match.
     *
     * @return MorphToMany<TagTeam, $this, EventMatchCompetitor>
     */
    public function tagTeams(): MorphToMany
    {
        return $this->morphedByMany(TagTeam::class, 'competitor', 'events_matches_competitors')
            ->using(EventMatchCompetitor::class)
            ->withPivot('side_number');
    }

    /**
     * Get the result of the match.
     *
     * @return HasOne<EventMatchResult, $this>
     */
    public function result(): HasOne
    {
        return $this->hasOne(EventMatchResult::class);
    }

    /**
     * Get all winners of the match through the result.
     *
     * @return HasManyThrough<EventMatchWinner, EventMatchResult, $this>
     */
    public function winners(): HasManyThrough
    {
        return $this->hasManyThrough(EventMatchWinner::class, EventMatchResult::class);
    }

    /**
     * Get all losers of the match through the result.
     *
     * @return HasManyThrough<EventMatchLoser, EventMatchResult, $this>
     */
    public function losers(): HasManyThrough
    {
        return $this->hasManyThrough(EventMatchLoser::class, EventMatchResult::class);
    }
}
