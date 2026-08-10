<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function currentTagTeam(): BelongsToOne;

    public function previousTagTeam(): BelongsToOne;

    /**
     * @return BelongsToMany<TagTeam, TModel, TPivotModel>
     */
    public function previousTagTeams(): BelongsToMany;

    public function isAMemberOfCurrentTagTeam(): bool;
}
