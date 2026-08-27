<?php

declare(strict_types=1);

namespace App\Services\Roster\TagTeams;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\RetirementPeriodManager;
use App\Lifecycle\TagTeamRetirementEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class TagTeamUnretirementService
{
    public function __construct(
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly TagTeamRetirementEligibility $eligibility,
    ) {}

    /**
     * @param  Closure(TagTeam, Carbon): void|null  $afterUnretirement
     */
    public function unretire(
        TagTeam $tagTeam,
        Carbon $unretirementDate,
        bool $requireAvailablePartners = true,
        ?Closure $afterUnretirement = null,
    ): void {
        DB::transaction(function () use ($tagTeam, $unretirementDate, $requireAvailablePartners, $afterUnretirement): void {
            $lockedTagTeam = TagTeam::query()
                ->whereKey($tagTeam->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanUnretire($lockedTagTeam, $requireAvailablePartners);
            $this->retirementPeriods->end($lockedTagTeam, $unretirementDate, LifecycleTransitionType::Unretired);
            $afterUnretirement?->__invoke($lockedTagTeam, $unretirementDate);
        });
    }
}
