<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableMembershipData;
use App\Models\Stables\Stable;
use App\Services\StableMembershipService;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Remove members from a stable.
 *
 * This is a cross-cutting action used by multiple workflows:
 * Disband, Delete, Retire, Split, etc. It handles the common
 * operation of ending member tenures with a stable.
 */
class RemoveStableMembersAction
{
    use AsAction;

    /**
     * Remove members from a stable.
     *
     * @param  Stable  $stable  The stable to remove members from
     * @param  StableMembershipData  $members  The members to remove
     * @param  Carbon  $removalDate  The date they left
     */
    public function handle(Stable $stable, StableMembershipData $members, Carbon $removalDate): void
    {
        if ($members->isNotEmpty()) {
            $membershipService = app(StableMembershipService::class);
            $membershipService->removeMembers($stable, $members, $removalDate);
        }
    }
}
