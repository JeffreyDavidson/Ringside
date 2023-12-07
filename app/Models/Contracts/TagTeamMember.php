<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Ankurk91\Eloquent\Relations\BelongsToOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface TagTeamMember
{
    /**
     * Get the tag teams the member has been a member of.
     */
    public function tagTeams(): BelongsToMany;

    /**
     * Get the current tag team the member belongs to.
     */
    public function currentTagTeam(): BelongsToOne;

    /**
     * Get the previous tag team the member has belonged to.
     */
    public function previousTagTeam(): BelongsToOne;

    /**
     * Get the previous tag teams the member has belonged to.
     */
    public function previousTagTeams(): BelongsToMany;

    /**
     * Determine if wrestler can is a member of a current tag team.
     */
    public function isAMemberOfCurrentTagTeam(): bool;
}
