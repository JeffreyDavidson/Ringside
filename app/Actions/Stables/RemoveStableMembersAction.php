<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableMembershipData;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Models\Roster\Stables\Stable;
use App\Services\Roster\Stables\StableMembershipService;
use Illuminate\Support\Carbon;

/**
 * Remove members from a stable.
 *
 * This is a cross-cutting action used by multiple workflows:
 * Disband, Delete, Retire, Split, etc. It handles the common
 * operation of ending member tenures with a stable.
 */
class RemoveStableMembersAction
{
    public function __construct(
        protected StableMembershipService $membershipService,
    ) {}

    /**
     * Remove members from a stable.
     *
     * @param  Stable  $stable  The stable to remove members from
     * @param  StableMembershipData  $members  The members to remove
     * @param  Carbon  $removalDate  The date they left
     * @throws InvalidDateRangeException When the removal date is in the future
     */
    public function handle(Stable $stable, StableMembershipData $members, Carbon $removalDate): void
    {
        if ($removalDate->isFuture()) {
            throw InvalidDateRangeException::futureNotAllowed($removalDate, 'Stable membership removal');
        }

        if ($members->isNotEmpty()) {
            $this->membershipService->removeMembers($stable, $members, $removalDate);
        }
    }
}
