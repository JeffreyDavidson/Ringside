<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;
use Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation;

class EventMatch extends Model
{
    use HasFactory;
    use HasMergedRelationships;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'match_type_id',
        'preview',
    ];

    /**
     * Get the match type of the match.
     */
    public function matchType(): BelongsTo
    {
        return $this->belongsTo(MatchType::class);
    }

    /**
     * Get the referees assigned to the match.
     */
    public function referees(): BelongsToMany
    {
        return $this->belongsToMany(Referee::class);
    }

    /**
     * Get the titles being competed for in the match.
     */
    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(Title::class);
    }

    /**
     * Get all fo the event match competitors for the match.
     */
    public function competitors(): MergedRelation
    {
        return $this->mergedRelationWithModel(EventMatchCompetitor::class, 'all_match_competitors');
    }

    /**
     * Get the wrestlers involved in the match.
     */
    public function wrestlers(): MorphToMany
    {
        return $this->morphedByMany(Wrestler::class, 'competitor', 'event_match_competitors')
            ->using(EventMatchCompetitor::class);
    }

    /**
     * Get the tag teams involved in the match.
     */
    public function tagTeams(): MorphToMany
    {
        return $this->morphedByMany(TagTeam::class, 'competitor', 'event_match_competitors')
            ->using(EventMatchCompetitor::class);
    }
}
