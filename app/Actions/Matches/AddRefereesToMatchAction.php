<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Lifecycle\RosterBookingEligibility;
use App\Models\Matches\EventMatch;
use App\Models\Referees\Referee;
use App\Services\MatchAssignmentConflictService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AddRefereesToMatchAction
{
    public function __construct(
        protected MatchAssignmentConflictService $conflictService,
    ) {}

    /**
     * Add referees to an event match.
     *
     * This handles the complete referee assignment workflow for matches:
     * - Validates referees are active, employed, and available for officiating
     * - Assigns qualified referees to officiate the match with proper authority
     * - Creates referee records linking officials to the match for accountability
     * - Ensures proper match officiating is established for legitimate competition
     * - Validates referees meet qualification requirements for the match type
     * - Prevents referee conflicts and double-booking scenarios
     *
     * BUSINESS RULES:
     * - Referees must be employed and active (not injured, suspended, or retired)
     * - Referees cannot officiate matches involving conflicts of interest
     * - Referees cannot be double-booked for the same event date
     * - Match must have at least one qualified referee assigned
     * - Special match types may require certified referees
     *
     * BUSINESS IMPACT:
     * - Ensures legitimate match outcomes and regulatory compliance
     * - Establishes official authority for match decisions and rule enforcement
     * - Supports referee payroll and appearance fee calculations
     * - Maintains match integrity and credibility for fans and stakeholders
     * - Enables proper disciplinary actions and match result validation
     *
     * @param  EventMatch  $eventMatch  The match to add referees to
     * @param  Collection<int, Referee>  $referees  The referees to assign for officiating
     */
    public function handle(EventMatch $eventMatch, Collection $referees): void
    {
        $requestedReferees = $referees->unique('id')->values();

        if ($requestedReferees->isEmpty() || $requestedReferees->contains(
            fn (Referee $referee): bool => ! RosterBookingEligibility::allows($referee)
        )) {
            throw EntityNotAvailableException::forMatchAssignment('referees');
        }

        DB::transaction(function () use ($eventMatch, $requestedReferees): void {
            $this->conflictService->ensureRefereesCanBeAssigned($eventMatch, $requestedReferees);

            $requestedReferees->each(function (Referee $referee) use ($eventMatch): void {
                $eventMatch->referees()->attach($referee->id);
            });
        });
    }
}
