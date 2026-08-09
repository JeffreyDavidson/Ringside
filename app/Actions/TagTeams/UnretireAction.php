<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Concerns\UnretirementCascadeStrategy;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException;
use App\Lifecycle\RetirementPeriodManager;
use App\Models\TagTeams\TagTeam;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UnretireAction
{
    public function __construct(private readonly RetirementPeriodManager $retirementPeriods) {}

    /**
     * Unretire a retired tag team and return them to active competition.
     *
     * This handles the complete tag team comeback workflow using cascade strategies:
     * - Validates the tag team can come out of retirement (business rule compliance)
     * - Ends the current retirement period with the specified date
     * - Uses UnretirementCascadeStrategy for consistent member unretirement
     * - Optionally employs the team immediately through employment cascade
     * - Flexible partner requirements for different unretirement scenarios
     * - Restores the tag team to available status for match bookings
     * - Makes the team available for championship opportunities again
     * - Preserves all historical retirement and partnership records
     * - Graceful error handling - individual member failures don't stop team unretirement
     *
     * ARCHITECTURAL PATTERN:
     * Uses RetirementPeriodManager for persistence and UnretirementCascadeStrategy
     * for relationship management and optional employment.
     *
     * @param  TagTeam  $tagTeam  The tag team to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     * @param  bool  $unretirePartners  Whether to unretire available partners (default: true)
     * @param  bool  $employImmediately  Whether to employ the team immediately (default: true)
     * @param  bool  $requireAvailablePartners  Whether to require available partners (default: true)
     * @throws CannotBeUnretiredException When tag team cannot be unretired due to business rules
     */
    public function handle(
        TagTeam $tagTeam,
        ?Carbon $unretiredDate = null,
        bool $unretirePartners = true,
        bool $employImmediately = true,
        bool $requireAvailablePartners = true
    ): void {
        $tagTeam->ensureCanBeUnretired($requireAvailablePartners);

        $unretiredDate = DateHelper::resolveDate($unretiredDate);

        DB::transaction(function () use ($tagTeam, $unretiredDate, $unretirePartners, $employImmediately): void {
            $this->retirementPeriods->end($tagTeam, $unretiredDate);

            // Handle member unretirement using cascade strategy
            UnretirementCascadeStrategy::conditionalMembers($unretirePartners)($tagTeam, $unretiredDate, 'unretire');

            // Handle immediate employment using cascade strategy
            UnretirementCascadeStrategy::employmentFollowup($employImmediately)($tagTeam, $unretiredDate, 'unretire');
        });
    }
}
