<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableMembershipData;
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
        protected StableMembershipService $membershipService
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
            $primaryStable->ensureCanBeMergedWith($secondaryStable);

            $members = new StableMembershipData(
                wrestlers: $secondaryStable->currentWrestlers,
                tagTeams: $secondaryStable->currentTagTeams,
            );

            $this->membershipService->removeMembers($secondaryStable, $members, $date);
            $this->membershipService->addMembers($primaryStable, $members, $date);
            $secondaryStable->delete();
        });
    }
}
