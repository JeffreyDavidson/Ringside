<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Stables\StableStatus;
use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;

/**
 * Service for managing stable lifecycle operations.
 *
 * Handles activity periods, status transitions, retirement records,
 * and other lifecycle-related operations for stables.
 */
class StableLifecycleService
{
    /**
     * Create or update an activity period for a stable.
     *
     * Used by EstablishAction and ReuniteAction to make stables active.
     *
     * @param  Stable  $stable  The stable to activate
     * @param  Carbon  $startDate  The activation start date
     */
    public function createActivityPeriod(Stable $stable, Carbon $startDate): void
    {
        $stable->activityPeriods()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $startDate->toDateTimeString()]
        );
    }

    /**
     * End the current activity period for a stable.
     *
     * Used by DisbandAction, DeleteAction, and RetireAction to deactivate stables.
     *
     * @param  Stable  $stable  The stable to deactivate
     * @param  Carbon  $endDate  The deactivation date
     */
    public function endActivityPeriod(Stable $stable, Carbon $endDate): void
    {
        $currentActivityPeriod = $stable->currentActivityPeriod()->first();
        if ($currentActivityPeriod) {
            $currentActivityPeriod->update(['ended_at' => $endDate]);
        }
    }

    /**
     * Create a retirement record for a stable.
     *
     * Used by RetireAction to formally retire a stable.
     *
     * @param  Stable  $stable  The stable to retire
     * @param  Carbon  $retirementDate  The retirement date
     */
    public function createRetirementRecord(Stable $stable, Carbon $retirementDate): void
    {
        $stable->retirements()->create([
            'started_at' => $retirementDate,
            'ended_at' => null,
        ]);
    }

    /**
     * End the current retirement record for a stable.
     *
     * Used by UnretireAction to end a stable's retirement.
     *
     * @param  Stable  $stable  The stable to unretire
     * @param  Carbon  $unretirementDate  The unretirement date
     */
    public function endRetirementRecord(Stable $stable, Carbon $unretirementDate): void
    {
        $stable->retirements()->where('ended_at', null)->update(['ended_at' => $unretirementDate]);
    }

    /**
     * Update stable status.
     *
     * Used for direct status changes like in UnretireAction.
     *
     * @param  Stable  $stable  The stable to update
     * @param  StableStatus  $status  The new status
     */
    public function updateStatus(Stable $stable, StableStatus $status): void
    {
        $stable->update(['status' => $status]);
    }

    /**
     * Complete stable lifecycle transition from retired to inactive.
     *
     * Used by UnretireAction to properly transition a retired stable.
     *
     * @param  Stable  $stable  The stable to transition
     * @param  Carbon  $unretirementDate  The unretirement date
     */
    public function transitionFromRetired(Stable $stable, Carbon $unretirementDate): void
    {
        $this->endRetirementRecord($stable, $unretirementDate);
        $this->updateStatus($stable, StableStatus::Inactive);
    }

    /**
     * Complete stable lifecycle transition to retirement.
     *
     * Used by RetireAction to properly retire a stable.
     *
     * @param  Stable  $stable  The stable to retire
     * @param  Carbon  $retirementDate  The retirement date
     */
    public function transitionToRetired(Stable $stable, Carbon $retirementDate): void
    {
        // End activity if currently active
        if ($stable->isCurrentlyActive()) {
            $this->endActivityPeriod($stable, $retirementDate);
        }

        $this->createRetirementRecord($stable, $retirementDate);
    }
}
