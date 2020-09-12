<?php

namespace App\Models\Concerns;

use App\Models\TagTeam;

trait CanBeTagTeamPartner
{
    /**
     * Get the tag team history the wrestler has belonged to.
     *
     * @return App\Eloquent\Relationships\BelongsToMany
     */
    public function tagTeams()
    {
        return $this->belongsToMany(TagTeam::class, 'tag_team_wrestler');
    }

    /**
     * Get the current tag team of the wrestler.
     *
     * @return App\Eloquent\Relationships\BelongsToMany
     */
    public function currentTagTeam()
    {
        return $this->belongsTo(TagTeam::class, 'tag_team_wrestler')
                ->where('started_at', '<=', now())
                ->whereNull('ended_at')
                ->limit(1);
    }

    /**
     * Get the previous tag teams the wrestler has belonged to.
     *
     * @return App\Eloquent\Relationships\BelongsToMany
     */
    public function previousTagTeams()
    {
        return $this->tagTeams()
                    ->whereNotNull('ended_at');
    }

    /**
     * Undocumented function.
     *
     * @return void
     */
    public function getCurrentTagTeamAttribute()
    {
        if (! $this->relationLoaded('currentTagTeam')) {
            $this->setRelation('currentTagTeam', $this->currentTagTeam()->get());
        }

        return $this->getRelation('currentTagTeam')->first();
    }
}
