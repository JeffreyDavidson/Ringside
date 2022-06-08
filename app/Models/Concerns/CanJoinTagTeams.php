<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\TagTeam;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait CanJoinTagTeams
{
    /**
     * Get the tag teams the model has been belonged to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tagTeams()
    {
        return $this->belongsToMany(TagTeam::class, 'tag_team_wrestler')
            ->withPivot(['joined_at', 'left_at']);
    }

    /**
     * Get the previous tag teams the member has belonged to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousTagTeams()
    {
        return $this->tagTeams()
            ->withPivot(['joined_at', 'left_at'])
            ->wherePivotNotNull('left_at');
    }

    /**
     * Get the user's first name.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function currentTagTeam(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->tagTeams()->wherePivotNull('left_at')->first(),
        );
    }

    public function isAMemberOfCurrentTagTeam()
    {
        return $this->currentTagTeam !== null && $this->currentTagTeam->exists();
    }
}
