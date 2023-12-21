<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Ankurk91\Eloquent\HasBelongsToOne;
use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Models\TagTeam;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property ?TagTeam $currentTagTeam
 */
trait CanJoinTagTeams
{
    use HasBelongsToOne;

    /**
     * Get the tag teams the model has been belonged to.
     *
     * @return BelongsToMany<TagTeam>
     */
    public function tagTeams(): BelongsToMany
    {
        return $this->belongsToMany(TagTeam::class, 'tag_team_wrestler')
            ->withPivot(['joined_at', 'left_at']);
    }

    /**
     * Get the previous tag teams the member has belonged to.
     *
     * @return BelongsToMany<TagTeam>
     */
    public function previousTagTeams(): BelongsToMany
    {
        return $this->tagTeams()
            ->wherePivotNotNull('left_at');
    }

    /**
     * Get the previous tag team the member has belonged to.
     */
    public function previousTagTeam(): BelongsToOne
    {
        return $this->belongsToOne(TagTeam::class)
            ->wherePivotNotNull('left_at')
            ->withPivot(['joined_at', 'left_at'])
            ->orderByPivot('joined_at', 'desc');
    }

    /**
     * Get the model's current tag team.
     */
    public function currentTagTeam(): BelongsToOne
    {
        return $this->belongsToOne(TagTeam::class)
            ->wherePivotNull('left_at')
            ->withPivot(['joined_at', 'left_at']);
    }

    /**
     * Determine if wrestler can is a member of a current tag team.
     */
    public function isAMemberOfCurrentTagTeam(): bool
    {
        return $this->currentTagTeam !== null && $this->currentTagTeam->exists();
    }
}
