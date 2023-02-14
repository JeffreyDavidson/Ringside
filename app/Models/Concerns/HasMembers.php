<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\StableMember;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;
use Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation;

trait HasMembers
{
    use HasMergedRelationships;

    /**
     * Get the wrestlers belonging to the stable.
     */
    public function wrestlers(): MorphToMany
    {
        return $this->morphedByMany(Wrestler::class, 'member', 'stable_members')
            ->using(StableMember::class)
            ->withPivot(['joined_at', 'left_at']);
    }

    /**
     * Get all current wrestlers that are members of the stable.
     */
    public function currentWrestlers(): MorphToMany
    {
        return $this->wrestlers()
            ->wherePivotNull('left_at');
    }

    /**
     * Get all previous wrestlers that were members of the stable.
     */
    public function previousWrestlers(): MorphToMany
    {
        return $this->wrestlers()
            ->wherePivotNotNull('left_at');
    }

    /**
     * Get the tag teams belonging to the stable.
     */
    public function tagTeams(): MorphToMany
    {
        return $this->morphedByMany(TagTeam::class, 'member', 'stable_members')
            ->using(StableMember::class)
            ->withPivot(['joined_at', 'left_at']);
    }

    /**
     * Get all current tag teams that are members of the stable.
     */
    public function currentTagTeams(): MorphToMany
    {
        return $this->tagTeams()
            ->wherePivotNull('left_at');
    }

    /**
     * Get all previous tag teams that were members of the stable.
     */
    public function previousTagTeams(): MorphToMany
    {
        return $this->tagTeams()
            ->wherePivotNotNull('left_at');
    }

    /**
     * Get the members belonging to the stable.
     */
    public function allMembers(): MergedRelation
    {
        return $this->mergedRelation('all_stable_members');
    }

    /**
     * Get all current members of the stable.
     */
    public function currentMembers(): MergedRelation
    {
        return $this->mergedRelation('current_stable_members');
    }

    /**
     * Get all previous members of the stable.
     */
    public function previousMembers(): MergedRelation
    {
        return $this->mergedRelation('previous_stable_members');
    }
}
