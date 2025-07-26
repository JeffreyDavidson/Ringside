<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Ankurk91\Eloquent\Relations\BelongsToOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface TagTeamMember
{
    public function tagTeams(): BelongsToMany;

    public function currentTagTeam(): BelongsToOne;

    public function previousTagTeam(): BelongsToOne;

    public function previousTagTeams(): BelongsToMany;

    public function isAMemberOfCurrentTagTeam(): bool;
}
