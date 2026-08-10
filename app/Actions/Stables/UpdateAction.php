<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Models\Stables\Stable;
use App\Services\StableMembershipService;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    /**
     * Create a new update action instance.
     */
    public function __construct(
        protected EstablishAction $establishAction,
        protected StableMembershipService $membershipService,
    ) {}

    /**
     * Update a stable.
     *
     * This handles the complete stable update workflow:
     * - Updates stable information (name, description)
     * - Handles establishment date changes if allowed
     * - Updates stable membership (wrestlers, tag teams, managers)
     * - Maintains stable integrity and member relationships
     *
     * @param  Stable  $stable  The stable to update
     * @param  StableData  $stableData  The updated stable information
     * @return Stable The updated stable instance
     */
    public function handle(Stable $stable, StableData $stableData): Stable
    {
        return DB::transaction(function () use ($stable, $stableData): Stable {
            $stable->update([
                'name' => $stableData->getTrimmedName(),
            ]);

            if ($stableData->hasStartDate()) {
                $activityPeriod = $stable->firstActivityPeriod()->first();

                if ($activityPeriod) {
                    $activityPeriod->update([
                        'started_at' => $stableData->start_date,
                        'ended_at' => $stableData->end_date,
                    ]);
                } else {
                    $this->establishAction->handle($stable, $stableData->start_date);
                }
            }

            // Update stable membership using service
            $this->membershipService->updateMembership($stable, $stableData->members, now());

            return $stable;
        });
    }
}
