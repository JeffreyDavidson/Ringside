<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\TagTeam;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property ?TagTeam $currentTagTeam
 */
trait CanJoinTagTeams
{
    /**
     * Get the tag teams the model has been belonged to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tagTeams(): BelongsToMany
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
     * Get the model's current tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentTagTeam(): BelongsTo
    {
        return $this->belongsTo(TagTeam::class, 'current_tag_team_id');
    }

    /**
     * Determine if wrestler can is a member of a current tag team.
     *
     * @return bool
     */
    public function isAMemberOfCurrentTagTeam()
    {
        return $this->currentTagTeam !== null && $this->currentTagTeam->exists();
    }
}
