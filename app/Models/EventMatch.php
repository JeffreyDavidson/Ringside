<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;

class EventMatch extends Model
{
    use HasFactory,
        HasMergedRelationships;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['event_id', 'match_type_id', 'preview'];

    /**
     * Get the match type of the match.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function matchType()
    {
        return $this->belongsTo(MatchType::class);
    }

    /**
     * Get the referees assigned to the match.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function referees()
    {
        return $this->belongsToMany(Referee::class);
    }

    /**
     * Get the titles being competed for in the match.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function titles()
    {
        return $this->belongsToMany(Title::class);
    }

    /**
     * Get all fo the event match competitors for the match.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function competitors()
    {
        return $this->mergedRelationWithModel(EventMatchCompetitor::class, 'all_match_competitors');
    }

    /**
     * Get the wrestlers involved in the match.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function wrestlers()
    {
        return $this->morphedByMany(Wrestler::class, 'competitor', 'event_match_competitors')
            ->using(EventMatchCompetitor::class);
    }

    /**
     * Get the tag teams involved in the match.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function tagTeams()
    {
        return $this->morphedByMany(TagTeam::class, 'competitor', 'event_match_competitors')
            ->using(EventMatchCompetitor::class);
    }
}
