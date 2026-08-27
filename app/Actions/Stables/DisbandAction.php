<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Roster\Stables\Stable;
use App\Services\StableDisbandService;
use App\Services\StableMembershipService;
use Illuminate\Support\Carbon;

class DisbandAction
{
    /**
     * Create a new disband action instance.
     */
    public function __construct(
        protected RemoveStableMembersAction $removeStableMembersAction,
        protected StableDisbandService $disbandment,
        protected StableMembershipService $membershipService,
    ) {}

    /**
     * Disband a stable and remove its current members.
     */
    public function handle(Stable $stable, ?Carbon $disbandDate = null): void
    {
        $this->disbandment->disband($stable, $disbandDate ?? now(), function (Stable $lockedStable, Carbon $effectiveDate): void {
            $currentMembers = $this->membershipService->currentMembers($lockedStable);

            if ($currentMembers->isNotEmpty()) {
                $this->removeStableMembersAction->handle(
                    $lockedStable,
                    $currentMembers,
                    $effectiveDate,
                );
            }
        });
    }
}
