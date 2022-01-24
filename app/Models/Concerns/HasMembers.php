<?php

namespace App\Models\Concerns;

use App\Models\StableMember;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;

trait HasMembers
{
    use HasMergedRelationships;

    /**
     * Get the wrestlers belonging to the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function wrestlers()
    {
        return $this->morphedByMany(Wrestler::class, 'member', 'stable_members')
            ->using(StableMember::class)
            ->withPivot(['joined_at', 'left_at']);
    }

    /**
     * Get all current wrestlers that are members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function currentWrestlers()
    {
        return $this->wrestlers()
            ->wherePivotNull('left_at');
    }

    /**
     * Get all previous wrestlers that were members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function previousWrestlers()
    {
        return $this->wrestlers()
            ->wherePivotNotNull('left_at');
    }

    /**
     * Get the tag teams belonging to the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function tagTeams()
    {
        return $this->morphedByMany(TagTeam::class, 'member', 'stable_members')
            ->using(StableMember::class)
            ->withPivot(['joined_at', 'left_at']);
    }

    /**
     * Get all current tag teams that are members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function currentTagTeams()
    {
        return $this->tagTeams()
            ->wherePivotNull('left_at');
    }

    /**
     * Get all previous tag teams that were members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function previousTagTeams()
    {
        return $this->tagTeams()
            ->wherePivotNotNull('left_at');
    }

    /**
     * Get the members belonging to the stable.
     *
     * @return \Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation
     */
    public function allMembers()
    {
        return $this->mergedRelation('all_stable_members');
    }

    /**
     * Get all current members of the stable.
     *
     * @return \Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation
     */
    public function currentMembers()
    {
        return $this->mergedRelation('current_stable_members');
    }

    /**
     * Get all previous members of the stable.
     *
     * @return \Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation
     */
    public function previousMembers()
    {
        return $this->mergedRelation('previous_stable_members');
    }
}
