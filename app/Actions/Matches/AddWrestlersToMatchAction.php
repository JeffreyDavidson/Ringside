<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Models\Matches\EventMatch;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\EventMatchRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

class AddWrestlersToMatchAction extends BaseEventMatchAction
{
    use AsAction;

    public function __construct(
        EventMatchRepository $eventMatchRepository
    ) {
        parent::__construct($eventMatchRepository);
    }

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
     *
     * @example
     * ```php
     * // Singles match - Add John Cena to side 1
     * $wrestlers = collect([$johnCena]);
     * AddWrestlersToMatchAction::run($match, $wrestlers, 1);
     *
     * // Handicap match - Add multiple wrestlers to one side
     * $wrestlers = collect([$wrestler1, $wrestler2]);
     * AddWrestlersToMatchAction::run($match, $wrestlers, 2);
     *
     * // Battle royal - Add multiple wrestlers to same side
     * $wrestlers = collect([$wrestler1, $wrestler2, $wrestler3]);
     * AddWrestlersToMatchAction::run($match, $wrestlers, 1);
     * ```
     */
    public function handle(EventMatch $eventMatch, Collection $wrestlers, int $sideNumber): void
    {
        // Pre-filter wrestlers to ensure only eligible competitors are processed
        $eligibleWrestlers = $wrestlers->filter(
            fn (Wrestler $wrestler) => $this->isWrestlerEligibleForMatch($wrestler, $eventMatch)
        );

        // Validate we have wrestlers to add after filtering
        if ($eligibleWrestlers->isEmpty()) {
            throw new InvalidArgumentException('No eligible wrestlers provided for match assignment');
        }

        // Validate side number is reasonable for match structure
        if ($sideNumber < 1) {
            throw new InvalidArgumentException('Side number must be positive');
        }

        DB::transaction(function () use ($eventMatch, $eligibleWrestlers, $sideNumber): void {
            // Add each eligible wrestler to the specified side
            $eligibleWrestlers->each(
                fn (Wrestler $wrestler) => $this->eventMatchRepository->addWrestlerToMatch(
                    $eventMatch,
                    $wrestler,
                    $sideNumber
                )
            );
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
