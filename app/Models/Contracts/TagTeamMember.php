<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 */
interface TagTeamMember
{
    /**
     * @return BelongsToMany<TagTeam, TDeclaringModel>
     */
    public function tagTeams(): BelongsToMany;

    public function currentTagTeam(): BelongsToOne;

    public function previousTagTeam(): BelongsToOne;

    /**
     * @return BelongsToMany<TagTeam, TDeclaringModel>
     */
    public function previousTagTeams(): BelongsToMany;

    public function isAMemberOfCurrentTagTeam(): bool;
}
