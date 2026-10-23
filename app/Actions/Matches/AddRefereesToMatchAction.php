<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Enums\Matches\MatchParticipantKind;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Lifecycle\Matches\MatchAssignmentEligibility;
use App\Lifecycle\Matches\MatchSchedulingLock;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Lifecycle\Roster\RosterDateAvailability;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Collection;

class AddRefereesToMatchAction
{
    public function __construct(
        private readonly MatchSchedulingLock $schedulingLock,
        private readonly MatchAssignmentEligibility $assignmentEligibility,
        private readonly RosterBookingEligibility $bookingEligibility,
        private readonly RosterDateAvailability $dateAvailability,
    ) {}

    /**
     * Add referees to an event match.
     *
     * Runs inside the caller's transaction: the caller has locked the match and its event set with
     * MatchSchedulingLock::lockMatchWithEventSet() and checked that every participant belongs to the event's
     * promotion (MatchConfigurationRequirements::ensureWithinEventPromotion()).
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
     * @param  Collection<int, Referee>  $referees
     * @param  list<string>  $retainedKeys  Server-derived assignments retained by the locked match update.
     */
    public function handle(EventMatch $lockedMatch, Collection $referees, array $retainedKeys = []): void
    {
        $requestedReferees = $referees->unique('id')->values();

        if ($requestedReferees->isEmpty()) {
            throw EntityNotAvailableException::forMatchAssignment(MatchParticipantKind::Referee);
        }

        $conflictingEventIds = $this->schedulingLock->lockConflictingEventIds($lockedMatch);
        $event = $lockedMatch->event()->firstOrFail();
        $lockedReferees = Referee::query()
            ->whereKey($requestedReferees->pluck('id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($lockedReferees->count() !== $requestedReferees->count() || $lockedReferees->contains(
            fn (Referee $referee): bool => ! $this->bookingEligibility->allows($referee, $event->date),
        )) {
            throw EntityNotAvailableException::forMatchAssignment(MatchParticipantKind::Referee);
        }

        $this->assignmentEligibility->ensureRefereesCanBeAssigned($lockedMatch->event_id, $conflictingEventIds, $lockedReferees);

        foreach ($lockedReferees as $person) {
            if (! in_array(RosterDateAvailability::key($person), $retainedKeys, true)) {
                $this->dateAvailability->ensureForEvent($person, $event);
            }
        }

        $lockedMatch->referees()->syncWithoutDetaching($lockedReferees->modelKeys());
    }
}
