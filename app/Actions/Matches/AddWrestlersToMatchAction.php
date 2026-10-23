<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Enums\Matches\MatchType;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Lifecycle\Matches\MatchAssignmentEligibility;
use App\Lifecycle\Matches\MatchConfigurationRequirements;
use App\Lifecycle\Matches\MatchSchedulingLock;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Lifecycle\Roster\RosterDateAvailability;
use App\Lifecycle\Tournaments\TournamentMatchRequirements;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AddWrestlersToMatchAction
{
    public function __construct(
        protected MatchSchedulingLock $schedulingLock,
        protected MatchAssignmentEligibility $assignmentEligibility,
        private readonly RosterBookingEligibility $bookingEligibility,
        private readonly RosterDateAvailability $dateAvailability,
        private readonly MatchConfigurationRequirements $configurationRequirements,
        private readonly TournamentMatchRequirements $tournamentRequirements,
    ) {}

    /**
     * Add wrestlers to an event match.
     *
     * This handles the complete wrestler assignment workflow for matches:
     * - Validates wrestlers are available and eligible for competition
     * - Assigns individual wrestlers to a specific side/team in the match
     * - Creates competitor records linking wrestlers to the match with proper side allocation
     * - Maintains match integrity and side assignments for balanced competition
     * - Ensures wrestlers are not double-booked or conflicted on the event date
     * - Validates wrestlers meet match requirements (employment status, injury status)
     *
     * BUSINESS RULES:
     * - Wrestlers must be employed and active (not injured, suspended, or retired)
     * - Wrestlers cannot be assigned to multiple sides in the same match
     * - Wrestlers cannot be double-booked for the same event date
     * - Side numbers must be valid for the match type
     *
     * BUSINESS IMPACT:
     * - Creates the foundation for match competition structure
     * - Enables proper match result tracking and championship changes
     * - Establishes competitor relationships for booking and storyline continuity
     * - Supports payroll and appearance fee calculations
     *
     * @param  EventMatch  $eventMatch  The match to add wrestlers to
     * @param  Collection<int, Wrestler>  $wrestlers  The wrestlers to add to the match
     * @param  int  $sideNumber  The side/team number for the wrestlers (1, 2, 3, etc.)
     */
    public function handle(EventMatch $eventMatch, Collection $wrestlers, int $sideNumber): void
    {
        $requestedWrestlers = $wrestlers->unique('id')->values();

        if ($requestedWrestlers->isEmpty()) {
            throw EntityNotAvailableException::forMatchAssignment('wrestlers');
        }

        // Validate side number is reasonable for match structure
        if ($sideNumber < 1) {
            throw InvalidMatchConfigurationException::invalidSideNumber($sideNumber);
        }

        DB::transaction(function () use ($eventMatch, $requestedWrestlers, $sideNumber): void {
            $lockedMatch = $this->schedulingLock->lockMatchWithEventSet($eventMatch);
            $this->configurationRequirements->ensureParticipantsWithinEventPromotion(
                $lockedMatch->event()->firstOrFail(),
                'wrestlers',
                $requestedWrestlers->all(),
            );
            $this->handleWithinTransaction($lockedMatch, $requestedWrestlers, $sideNumber);
        }, attempts: 3);
    }

    /**
     * Assign wrestlers while the caller owns the match transaction and lock.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     * @param  list<string>  $retainedKeys  Server-derived assignments retained by the locked match update.
     */
    public function handleWithinTransaction(EventMatch $lockedMatch, Collection $wrestlers, int $sideNumber, array $retainedKeys = []): void
    {
        $this->tournamentRequirements->ensureCompetitors($lockedMatch, $wrestlers);
        $requestedWrestlers = $wrestlers->unique('id')->values();
        $conflictingEventIds = $this->schedulingLock->lockConflictingEventIds($lockedMatch);
        $event = $lockedMatch->event()->firstOrFail();
        $lockedWrestlers = Wrestler::query()
            ->whereKey($requestedWrestlers->pluck('id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($lockedWrestlers->count() !== $requestedWrestlers->count() || $lockedWrestlers->contains(
            fn (Wrestler $wrestler): bool => ! $this->bookingEligibility->allows($wrestler, $event->date),
        )) {
            throw EntityNotAvailableException::forMatchAssignment('wrestlers');
        }

        $this->assignmentEligibility->ensureWrestlersCanBeAssigned(
            $conflictingEventIds,
            $lockedWrestlers,
            $lockedMatch,
        );

        foreach ($lockedWrestlers as $person) {
            if (! in_array(RosterDateAvailability::key($person), $retainedKeys, true)) {
                $this->dateAvailability->ensureForEvent($person, $event);
            }
        }

        $side = $lockedMatch->sides()->firstOrCreate(['position' => $sideNumber]);

        $lockedWrestlers->each(function (Wrestler $wrestler) use ($lockedMatch, $side): void {
            $competitor = $lockedMatch->competitors()->create([
                'competitor_id' => $wrestler->id,
                'competitor_type' => $wrestler->getMorphClass(),
                'match_side_id' => $side->id,
            ]);

            if ($lockedMatch->match_type === MatchType::RoyalRumble) {
                $competitor->forceFill(['entry_order' => $side->position])->save();
            }
        });
    }
}
