<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property int $event_id
 * @property int $match_type_id
 * @property string|null $preview
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Collections\EventMatchCompetitorsCollection<int, \App\Models\EventMatchCompetitor> $competitors
 * @property-read \App\Models\Event|null $event
 * @property-read \App\Models\TFactory|null $use_factory
 * @property-read \App\Models\MatchType|null $matchType
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Referee> $referees
 * @property-read \App\Models\EventMatchResult|null $result
 * @property-read \App\Models\EventMatchCompetitor|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeam> $tagTeams
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Title> $titles
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wrestler> $wrestlers
 *
 * @method static \Database\Factories\EventMatchFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatch query()
 *
 * @mixin \Eloquent
 */
class EventMatch extends Model
{
    /** @use HasFactory<\Database\Factories\EventMatchFactory> */
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
        'match_type_id',
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
     * @return MorphToMany<Wrestler, $this>
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
     * @return MorphToMany<TagTeam, $this>
     */
    public function tagTeams(): MorphToMany
    {
        return $this->morphedByMany(TagTeam::class, 'competitor', 'events_matches_competitors')
            ->using(EventMatchCompetitor::class)
            ->withPivot('side_number');
    }

    /**
     * Get the tag teams involved in the match.
     *
     * @return HasOne<EventMatchResult, $this>
     */
    public function result(): HasOne
    {
        return $this->hasOne(EventMatchResult::class);
    }
}
