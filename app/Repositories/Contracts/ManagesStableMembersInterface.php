<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

/**
 * Contract for repositories that manage stable membership.
 *
 * This interface defines the methods required for repositories
 * that handle adding/removing members from stables. Members can be
 * wrestlers or tag teams only. Managers are NOT directly associated
 * with stables - they manage wrestlers/tag teams that are members.
 */
interface ManagesStableMembersInterface
{
    /**
     * Add a member to the stable.
     *
     * @param  Stable  $stable  The stable to add the member to
     * @param  Wrestler|TagTeam  $member  The member to add
     * @param  Carbon  $startDate  When the membership begins
     */
    public function addMember(Stable $stable, Wrestler|TagTeam $member, Carbon $startDate): void;

    /**
     * Remove a member from the stable.
     *
     * @param  Stable  $stable  The stable to remove the member from
     * @param  Wrestler|TagTeam  $member  The member to remove
     * @param  Carbon  $endDate  When the membership ends
     */
    public function removeMember(Stable $stable, Wrestler|TagTeam $member, Carbon $endDate): void;
}
