<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface TagTeamMember
{
    /**
     * Get the tag teams the member has been a member of.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tagTeams();

    /**
     * Get the current tag team the member belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function currentTagTeam();

    /**
     * Get the previous tag teams the member has belonged to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousTagTeams();
}
