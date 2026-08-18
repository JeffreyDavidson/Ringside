<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException;
use App\Lifecycle\RetirementPeriodManager;
use App\Lifecycle\TagTeamRetirementEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UnretireAction
{
    public function __construct(
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly UnretireCurrentMembersAction $unretireCurrentMembers,
        private readonly EmployAction $employ,
        private readonly TagTeamRetirementEligibility $eligibility,
    ) {}

    /**
     * Unretire a retired tag team and return them to active competition.
     *
     * This handles the complete tag team comeback workflow:
     * - Validates the tag team can come out of retirement (business rule compliance)
     * - Ends the current retirement period with the specified date
     * - Optionally unretires eligible current members through a typed action
     * - Optionally employs the team immediately through its employment action
     * - Flexible partner requirements for different unretirement scenarios
     * - Restores the tag team to available status for match bookings
     * - Makes the team available for championship opportunities again
     * - Preserves all historical retirement and partnership records
     * - Graceful error handling - individual member failures don't stop team unretirement
     *
     * @param  TagTeam  $tagTeam  The tag team to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     * @param  bool  $unretireMembers  Whether to unretire eligible current members (default: true)
     * @param  bool  $employImmediately  Whether to employ the team immediately (default: true)
     * @param  bool  $requireAvailablePartners  Whether to require available partners (default: true)
     * @throws CannotBeUnretiredException When tag team cannot be unretired due to business rules
     */
    public function handle(
        TagTeam $tagTeam,
        ?Carbon $unretiredDate = null,
        bool $unretireMembers = true,
        bool $employImmediately = true,
        bool $requireAvailablePartners = true
    ): void {
        $unretiredDate = $unretiredDate ?? now();

        DB::transaction(function () use ($tagTeam, $unretiredDate, $unretireMembers, $employImmediately, $requireAvailablePartners): void {
            $lockedTagTeam = TagTeam::query()->whereKey($tagTeam->getKey())->lockForUpdate()->firstOrFail();
            $this->eligibility->ensureCanUnretire($lockedTagTeam, $requireAvailablePartners);

            $this->retirementPeriods->end($lockedTagTeam, $unretiredDate, LifecycleTransitionType::Unretired);

            if ($unretireMembers) {
                $this->unretireCurrentMembers->handle($lockedTagTeam, $unretiredDate);
            }

            if ($employImmediately && ! $lockedTagTeam->isEmployed() && $lockedTagTeam->currentWrestlers()->exists()) {
                $this->employ->handle($lockedTagTeam, $unretiredDate);
            }
        });
    }
}
