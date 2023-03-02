<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\TagTeam;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property ?TagTeam $currentTagTeam
 */
trait CanJoinTagTeams
{
    /**
     * Get the tag teams the model has been belonged to.
     */
    public function tagTeams(): BelongsToMany
    {
        return $this->belongsToMany(TagTeam::class, 'tag_team_wrestler')
            ->withPivot(['joined_at', 'left_at']);
    }

    /**
     * Get the previous tag teams the member has belonged to.
     */
    public function previousTagTeams(): BelongsToMany
    {
        return $this->tagTeams()
            ->withPivot(['joined_at', 'left_at'])
            ->wherePivotNotNull('left_at');
    }

    /**
     * Get the previous tag team the member has belonged to.
     */
    public function previousTagTeam(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->previousTagTeams->first(),
        );
    }

    /**
     * Get the model's current tag team.
     */
    public function currentTagTeam(): BelongsTo
    {
        return $this->belongsTo(TagTeam::class, 'current_tag_team_id');
    }

    /**
     * Determine if wrestler can is a member of a current tag team.
     */
    public function isAMemberOfCurrentTagTeam(): bool
    {
        return $this->currentTagTeam !== null && $this->currentTagTeam->exists();
    }
}
