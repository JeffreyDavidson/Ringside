<?php

namespace App\Models\Concerns;

use App\Models\TagTeam;

trait TagTeamMember
{
    /**
     * Get the tag teams the model has been belonged to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tagTeams()
    {
        return $this->belongsToMany(TagTeam::class, 'tag_team_wrestler')->withPivot(['joined_at', 'left_at']);
    }

    /**
     * Get the current tag team the member belongs to.
     *
     * @return \Staudenmeir\EloquentHasManyDeep\HasRelationships\HasOneDeep
     */
    public function currentTagTeam()
    {
        return $this->hasOneDeep(TagTeam::class, ['tag_team_wrestler'])
                ->whereNull('tag_team_wrestler.left_at');
    }

    /**
     * Get the previous tag teams the member has belonged to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousTagTeams()
    {
        return $this->tagTeams()->whereNotNull('ended_at');
    }
}
