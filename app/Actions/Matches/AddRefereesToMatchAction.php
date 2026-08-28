<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Lifecycle\RosterBookingEligibility;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Services\Matches\EventMatchAssignmentService;
use App\Services\Matches\MatchAssignmentConflictService;
use Illuminate\Support\Collection;

class AddRefereesToMatchAction
{
    public function __construct(
        private readonly MatchAssignmentConflictService $conflictService,
        private readonly EventMatchAssignmentService $assignmentTransaction,
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

        if ($requestedReferees->isEmpty()) {
            throw EntityNotAvailableException::forMatchAssignment('referees');
        }

        $this->assignmentTransaction->execute($eventMatch, function (EventMatch $lockedMatch) use ($requestedReferees): void {
            $this->handleWithinTransaction($lockedMatch, $requestedReferees);
        });
    }

    /**
     * Assign referees while the caller owns the match transaction and lock.
     *
     * @param  Collection<int, Referee>  $referees
     */
    public function handleWithinTransaction(EventMatch $lockedMatch, Collection $referees): void
    {
        $requestedReferees = $referees->unique('id')->values();

        if ($requestedReferees->isEmpty()) {
            throw EntityNotAvailableException::forMatchAssignment('referees');
        }

        $conflictingEventIds = $this->conflictService->lockConflictingEventIds($lockedMatch);
        $lockedReferees = Referee::query()
            ->whereKey($requestedReferees->pluck('id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($lockedReferees->count() !== $requestedReferees->count() || $lockedReferees->contains(
            fn (Referee $referee): bool => ! RosterBookingEligibility::allows($referee)
        )) {
            throw EntityNotAvailableException::forMatchAssignment('referees');
        }

        $this->conflictService->ensureRefereesCanBeAssigned($lockedMatch->event_id, $conflictingEventIds, $lockedReferees);

        $lockedMatch->referees()->syncWithoutDetaching($lockedReferees->pluck('id'));
    }
}
