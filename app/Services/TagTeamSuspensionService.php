<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\SuspensionPeriodManager;
use App\Lifecycle\TagTeamSuspensionEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class TagTeamSuspensionService
{
    public function __construct(
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly TagTeamSuspensionEligibility $eligibility,
    ) {}

    /**
     * @param  Closure(TagTeam, Carbon): void|null  $afterSuspension
     */
    public function suspend(
        TagTeam $tagTeam,
        Carbon $suspensionDate,
        ?Closure $afterSuspension = null,
    ): void {
        DB::transaction(function () use ($tagTeam, $suspensionDate, $afterSuspension): void {
            $lockedTagTeam = TagTeam::query()
                ->whereKey($tagTeam->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanSuspend($lockedTagTeam);
            $this->suspensionPeriods->start($lockedTagTeam, $suspensionDate, LifecycleTransitionType::Suspended);
            $afterSuspension?->__invoke($lockedTagTeam, $suspensionDate);
        });
    }
}
