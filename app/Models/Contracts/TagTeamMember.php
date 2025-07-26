<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface TagTeamMember
{
    /**
     * @return BelongsToMany<TagTeam, static>
     */
    public function tagTeams(): BelongsToMany;

    public function currentTagTeam(): BelongsToOne;

    public function previousTagTeam(): BelongsToOne;

    /**
     * @return BelongsToMany<TagTeam, static>
     */
    public function previousTagTeams(): BelongsToMany;

    public function isAMemberOfCurrentTagTeam(): bool;
}
