<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\TagTeams\CannotBeReinstatedException;
use App\Lifecycle\SuspensionPeriodManager;
use App\Lifecycle\TagTeamSuspensionEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReinstateAction
{
    public function __construct(
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly ReinstateCurrentMembersAction $reinstateCurrentMembers,
        private readonly TagTeamSuspensionEligibility $eligibility,
    ) {}

    /**
     * Reinstate a suspended tag team.
     *
     * This handles the complete tag team reinstatement workflow:
     * - Validates the tag team can be reinstated (currently suspended)
     * - Ends the suspension through the shared lifecycle component
     * - Automatically cascades reinstatement to suspended wrestlers and managers
     * - Makes the team available for match bookings and championships again
     * - Keeps the primary change and member cascades in one transaction
     *
     * @param  TagTeam  $tagTeam  The tag team to reinstate
     * @param  Carbon|null  $reinstatementDate  The reinstatement date (defaults to now)
     * @throws CannotBeReinstatedException When tag team cannot be reinstated due to business rules
     */
    public function handle(TagTeam $tagTeam, ?Carbon $reinstatementDate = null): void
    {
        $reinstatementDate = DateHelper::resolveDate($reinstatementDate);

        DB::transaction(function () use ($tagTeam, $reinstatementDate): void {
            $lockedTagTeam = TagTeam::query()->whereKey($tagTeam->getKey())->lockForUpdate()->firstOrFail();
            $this->eligibility->ensureCanReinstate($lockedTagTeam);

            $this->suspensionPeriods->end($lockedTagTeam, $reinstatementDate, LifecycleTransitionType::Reinstated);
            $this->reinstateCurrentMembers->handle($lockedTagTeam, $reinstatementDate);
        });
    }
}
