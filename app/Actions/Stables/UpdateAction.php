<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Models\Roster\Stables\Stable;
use App\Services\Roster\Stables\StableMembershipService;
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
        if ($stableData->start_date && $stableData->end_date && $stableData->end_date->lt($stableData->start_date)) {
            throw InvalidDateRangeException::endBeforeStart(
                $stableData->start_date,
                $stableData->end_date,
                'stable activity',
            );
        }

        return DB::transaction(function () use ($stable, $stableData): Stable {
            $lockedStable = $stable->refreshForUpdate();

            $lockedStable->update([
                'name' => $stableData->getTrimmedName(),
            ]);

            $this->membershipService->updateMembership($lockedStable, $stableData->members, now());

            if ($stableData->hasStartDate()) {
                $activityPeriod = $lockedStable->firstActivityPeriod()->lockForUpdate()->first();

                if ($activityPeriod) {
                    $activityPeriod->update([
                        'started_at' => $stableData->start_date,
                        'ended_at' => $stableData->end_date,
                    ]);
                } else {
                    $this->establishAction->handle(
                        $lockedStable,
                        $stableData->start_date,
                        $stableData->end_date,
                    );
                }
            }

            return $lockedStable;
        });
    }
}
