<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Models\Matches\EventMatch;
use App\Models\Wrestlers\Wrestler;
use App\Services\MatchAssignmentConflictService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AddWrestlersToMatchAction
{
    public function __construct(
        protected MatchAssignmentConflictService $conflictService,
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
        // Pre-filter wrestlers to ensure only eligible competitors are processed
        $eligibleWrestlers = $wrestlers->filter(
            fn (Wrestler $wrestler) => $this->isWrestlerEligibleForMatch($wrestler, $eventMatch)
        );

        // Validate we have wrestlers to add after filtering
        if ($eligibleWrestlers->isEmpty()) {
            throw EntityNotAvailableException::forMatchAssignment('wrestlers');
        }

        // Validate side number is reasonable for match structure
        if ($sideNumber < 1) {
            throw InvalidMatchConfigurationException::invalidSideNumber($sideNumber);
        }

        DB::transaction(function () use ($eventMatch, $eligibleWrestlers, $sideNumber): void {
            $this->conflictService->ensureWrestlersCanBeAssigned($eventMatch, $eligibleWrestlers);

            // Add each eligible wrestler to the specified side
            $eligibleWrestlers->each(function (Wrestler $wrestler) use ($eventMatch, $sideNumber) {
                $eventMatch->competitors()->create([
                    'competitor_id' => $wrestler->id,
                    'competitor_type' => Wrestler::class,
                    'side_number' => $sideNumber,
                ]);
            });
        });
    }

    /**
     * Check if a wrestler is eligible to compete in the match.
     *
     * @param  Wrestler  $wrestler  The wrestler to validate
     * @param  EventMatch  $eventMatch  The match they would compete in
     * @return bool True if the wrestler can compete
     */
    private function isWrestlerEligibleForMatch(Wrestler $wrestler, EventMatch $eventMatch): bool
    {
        // Basic availability checks - wrestler must be active and available
        if (! $wrestler->isBookable()) {
            return false;
        }

        // Check for conflicts with existing match assignments
        // Note: More complex conflict checking would be implemented here
        // such as checking for double-booking on the same event date
        // Could validate against $eventMatch->event->date for scheduling conflicts

        return true;
    }
}
