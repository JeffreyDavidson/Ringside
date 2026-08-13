<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @template TPivotModel of Pivot The pivot model for the tag team relationship
 * @template TModel of Model The model that can be a tag team member
 *
 * @see CanJoinTagTeams For the trait implementation
 */
interface CanBeATagTeamMember
{
    /**
     * @return BelongsToMany<TagTeam, TModel, TPivotModel>
     */
    public function tagTeams(): BelongsToMany;

    /**
     * @return HasOneThrough<TagTeam, TPivotModel, TModel>
     */
    public function currentTagTeam(): HasOneThrough;

    /**
     * @return HasOneThrough<TagTeam, TPivotModel, TModel>
     */
    public function previousTagTeam(): HasOneThrough;

    /**
     * @return BelongsToMany<TagTeam, TModel, TPivotModel>
     */
    public function previousTagTeams(): BelongsToMany;
}
