<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    public function currentTagTeam(): BelongsTo;

    /**
     * Get the previous tag teams the member has belonged to.
     */
    public function previousTagTeams(): BelongsToMany;
}
