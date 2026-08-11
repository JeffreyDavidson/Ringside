<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableMembershipData;
use App\Exceptions\Roster\Stables\CannotBeMergedException;
use App\Models\Stables\Stable;
use App\Services\StableMembershipService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MergeStablesAction
{
    /**
     * Create a new merge stables action instance.
     */
    public function __construct(
        protected StableMembershipService $membershipService,
        protected EndActivityPeriodAction $endActivityPeriodAction,
    ) {}

    /**
     * Merge two stables into one.
     *
     * Transfers all members from the secondary stable to the primary stable
     * and optionally deletes the secondary stable if the operation is successful.
     *
     * @param  Stable  $primaryStable  The stable that will receive all members
     * @param  Stable  $secondaryStable  The stable that will be merged into the primary
     * @param  Carbon  $date  The date when the merge operation occurs
     */
    public function handle(
        Stable $primaryStable,
        Stable $secondaryStable,
        Carbon $date
    ): void {
        DB::transaction(function () use ($primaryStable, $secondaryStable, $date): void {
            [$firstStable, $secondStable] = $primaryStable->getKey() < $secondaryStable->getKey()
                ? [$primaryStable, $secondaryStable]
                : [$secondaryStable, $primaryStable];

            $firstLockedStable = Stable::query()->lockForUpdate()->findOrFail($firstStable->getKey());
            $secondLockedStable = Stable::query()->lockForUpdate()->findOrFail($secondStable->getKey());

            $lockedPrimaryStable = $firstLockedStable->is($primaryStable)
                ? $firstLockedStable
                : $secondLockedStable;
            $lockedSecondaryStable = $firstLockedStable->is($secondaryStable)
                ? $firstLockedStable
                : $secondLockedStable;

            $lockedPrimaryStable->ensureCanBeMergedWith($lockedSecondaryStable);

            $members = new StableMembershipData(
                wrestlers: $lockedSecondaryStable->currentWrestlers,
                tagTeams: $lockedSecondaryStable->currentTagTeams,
            );

            $unavailableMemberNames = $members->getUnavailableMemberNames();

            if ($unavailableMemberNames !== []) {
                throw CannotBeMergedException::membersUnavailable($unavailableMemberNames);
            }

            $this->membershipService->removeMembers($lockedSecondaryStable, $members, $date);
            $this->membershipService->addMembers($lockedPrimaryStable, $members, $date);
            $this->endActivityPeriodAction->handle($lockedSecondaryStable, $date);
            $lockedSecondaryStable->delete();
        });
    }
}
