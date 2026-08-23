<?php

declare(strict_types=1);

namespace App\Models\Matches;

use App\Builders\Matches\EventMatchBuilder;
use App\Builders\Matches\MatchSideBuilder;
use App\Collections\MatchCompetitorsCollection;
use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Models\Concerns\HasLifecycleTransitions;
use App\Models\Contracts\SoftDeletable;
use App\Models\Events\Event;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Database\Factories\Matches\MatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property int $match_number
 * @property MatchType $match_type
 * @property int|null $match_stipulation_id
 * @property MatchFinish|null $match_finish
 * @property int|null $winning_side_id
 * @property string|null $preview
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read MatchCompetitor|null $pivot
 * @property-read MatchCompetitorsCollection<int, MatchCompetitor> $competitors
 * @property-read Event $event
 * @property-read MatchStipulation|null $matchStipulation
 * @property-read MatchSide|null $winningSide
 * @property-read Collection<int, MatchSide> $sides
 * @property-read Collection<int, Referee> $referees
 * @property-read Collection<int, TagTeam> $tagTeams
 * @property-read Collection<int, Title> $titles
 * @property-read Collection<int, Wrestler> $wrestlers
 *
 * @method static \Database\Factories\Matches\MatchFactory factory($count = null, $state = [])
 * @method static EventMatchBuilder<static>|EventMatch forPastEvents()
 * @method static EventMatchBuilder<static>|EventMatch forHistory()
 * @method static EventMatchBuilder<static>|EventMatch forCompetitor(Wrestler|TagTeam $competitor)
 * @method static EventMatchBuilder<static>|EventMatch forEventId(int $eventId)
 * @method static EventMatchBuilder<static>|EventMatch forEventIds(\Illuminate\Support\Collection<int, int> $eventIds)
 * @method static EventMatchBuilder<static>|EventMatch forTagTeamId(int $tagTeamId)
 * @method static EventMatchBuilder<static>|EventMatch forReferee(Referee $referee)
 * @method static EventMatchBuilder<static>|EventMatch forRefereeId(int $refereeId)
 * @method static EventMatchBuilder<static>|EventMatch forWrestlerId(int $wrestlerId)
 * @method static EventMatchBuilder<static>|EventMatch latestEventFirst()
 * @method static EventMatchBuilder<static>|EventMatch newModelQuery()
 * @method static EventMatchBuilder<static>|EventMatch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatch onlyTrashed()
 * @method static EventMatchBuilder<static>|EventMatch query()
 * @method static EventMatchBuilder<static>|EventMatch withAnyRefereeIds(\Illuminate\Support\Collection<int, int> $refereeIds)
 * @method static EventMatchBuilder<static>|EventMatch withAnyTitleIds(\Illuminate\Support\Collection<int, int> $titleIds)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatch withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatch withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Table('events_matches')]
#[Fillable('event_id', 'match_number', 'match_type', 'match_stipulation_id', 'preview', 'match_finish', 'winning_side_id')]
#[UseEloquentBuilder(EventMatchBuilder::class)]
#[UseFactory(MatchFactory::class)]
class EventMatch extends Model implements SoftDeletable
{
    /** @use HasFactory<MatchFactory> */
    use HasFactory;

    use HasLifecycleTransitions;
    use SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'match_type' => MatchType::class,
            'match_finish' => MatchFinish::class,
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
        return $this->morphedByMany(Wrestler::class, 'competitor', (new MatchCompetitor())->getTable(), 'match_id')
            ->using(MatchCompetitor::class)
            ->withPivot('match_side_id');
    }

    /**
     * Get the tag teams involved in the match.
     *
     * @return MorphToMany<TagTeam, $this, MatchCompetitor>
     */
    public function tagTeams(): MorphToMany
    {
        return $this->morphedByMany(TagTeam::class, 'competitor', (new MatchCompetitor())->getTable(), 'match_id')
            ->using(MatchCompetitor::class)
            ->withPivot('match_side_id');
    }

    /** @return HasMany<MatchSide, $this> */
    public function sides(): HasMany
    {
        $relation = $this->hasMany(MatchSide::class, 'match_id');
        MatchSideBuilder::constrainToPositionOrder($relation->getQuery());

        return $relation;
    }

    /** @return BelongsTo<MatchSide, $this> */
    public function winningSide(): BelongsTo
    {
        return $this->belongsTo(MatchSide::class, 'winning_side_id');
    }
}
