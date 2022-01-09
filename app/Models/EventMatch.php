<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class EventMatch extends Model
{
    use HasFactory,
        HasRelationships;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['event_id', 'match_type_id', 'preview'];

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

    public function competitors()
    {
        return $this->hasMany(EventMatchCompetitor::class);
    }

    /**
     * Get the wrestlers involved in the match.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphedByMany
     */
    public function wrestlers()
    {
        return $this->morphedByMany(Wrestler::class, 'event_match_competitor', 'event_match_competitors')
                    ->using(EventMatchCompetitor::class);
    }

    /**
     * Get the tag teams involved in the match.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphedByMany
     */
    public function tagTeams()
    {
        return $this->morphedByMany(Wrestler::class, 'event_match_competitor', 'event_match_competitors')
                    ->using(EventMatchCompetitor::class);
    }
}
