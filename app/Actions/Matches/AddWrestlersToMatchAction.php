<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Enums\MatchType;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Matches\MatchAssignmentConflictService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AddWrestlersToMatchAction
{
    public function __construct(
        protected MatchAssignmentConflictService $conflictService,
        private readonly RosterBookingEligibility $bookingEligibility,
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
            $lockedMatch = $eventMatch->refreshForUpdate();
            $this->handleWithinTransaction($lockedMatch, $requestedWrestlers, $sideNumber);
        });
    }

    /**
     * Assign wrestlers while the caller owns the match transaction and lock.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function handleWithinTransaction(EventMatch $lockedMatch, Collection $wrestlers, int $sideNumber): void
    {
        $requestedWrestlers = $wrestlers->unique('id')->values();
        $conflictingEventIds = $this->conflictService->lockConflictingEventIds($lockedMatch);
        $lockedWrestlers = Wrestler::query()
            ->whereKey($requestedWrestlers->pluck('id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($lockedWrestlers->count() !== $requestedWrestlers->count() || $lockedWrestlers->contains(
            fn (Wrestler $wrestler): bool => ! $this->bookingEligibility->allows($wrestler)
        )) {
            throw EntityNotAvailableException::forMatchAssignment('wrestlers');
        }

        $this->conflictService->ensureCompetitorsCanBeAssigned(
            $conflictingEventIds,
            $lockedWrestlers,
            Wrestler::class,
            'Wrestler',
        );
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
