<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    /**
     * Create a new update action instance.
     */
    public function __construct(
        protected EstablishAction $establishAction,
        protected SynchronizeStableMembersAction $synchronizeStableMembersAction,
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
        if ($stableData->start_date instanceof Carbon && $stableData->end_date instanceof Carbon && $stableData->end_date->lt($stableData->start_date)) {
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

            $this->synchronizeStableMembersAction->handle($lockedStable, $stableData->members, now());

            if ($stableData->start_date instanceof Carbon) {
                $activityPeriod = $lockedStable->activityPeriods()
                    ->orderBy('started_at')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if ($activityPeriod) {
                    $endedAt = $this->endDateFor($lockedStable, $activityPeriod, $stableData);

                    if ($endedAt instanceof Carbon && $endedAt->lt($stableData->start_date)) {
                        throw InvalidDateRangeException::endBeforeStart(
                            $stableData->start_date,
                            $endedAt,
                            'stable activity',
                        );
                    }

                    $activityPeriod->update([
                        'started_at' => $stableData->start_date,
                        'ended_at' => $endedAt,
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

    /**
     * An ended first period never reopens through the edit form: a disbanded stable returns only
     * through ReuniteAction, and an earlier period cannot be closed or moved once later periods exist.
     */
    private function endDateFor(Stable $stable, ActivityPeriod $firstPeriod, StableData $stableData): ?Carbon
    {
        if ($firstPeriod->ended_at === null) {
            return $stableData->end_date;
        }

        if (! $stableData->end_date instanceof Carbon || $stable->activityPeriods()->whereKeyNot($firstPeriod->getKey())->exists()) {
            return $firstPeriod->ended_at;
        }

        return $stableData->end_date;
    }
}
