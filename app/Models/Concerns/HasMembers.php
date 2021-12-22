<?php

namespace App\Models\Concerns;

use App\Models\StableMember;
use App\Models\TagTeam;
use App\Models\Wrestler;

trait HasMembers
{
    /**
     * Get the wrestlers belonging to the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
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
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function currentWrestlers()
    {
        return $this->wrestlers()->whereNull('left_at');
    }

    /**
     * Get all previous wrestlers that were members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function previousWrestlers()
    {
        return $this->wrestlers()->whereNotNull('left_at');
    }

    /**
     * Get the tag teams belonging to the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
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
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function currentTagTeams()
    {
        return $this->tagTeams()->whereNull('left_at');
    }

    /**
     * Get all previous tag teams that were members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function previousTagTeams()
    {
        return $this->tagTeams()->whereNotNull('left_at');
    }

    /**
     * Get the members belonging to the stable.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getMembersAttribute()
    {
        $wrestlers = $this->wrestlers;
        $tagTeams = $this->tagTeams;

        $members = $wrestlers->merge($tagTeams);

        return $members;
    }

    /**
     * Get all current members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function currentMembers()
    {
        return $this->currentTagTeams()->currentWrestlers();
    }

    /**
     * Get all previous members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function previousMembers()
    {
        return $this->previousTagTeams()->previousWrestlers();
    }
}
