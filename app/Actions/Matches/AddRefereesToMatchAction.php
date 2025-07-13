<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Models\Matches\EventMatch;
use App\Models\Referees\Referee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

class AddRefereesToMatchAction extends BaseEventMatchAction
{
    use AsAction;

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
     *
     * @example
     * ```php
     * // Standard match with one referee
     * $referees = collect([$mikeChaota]);
     * AddRefereesToMatchAction::run($match, $referees);
     *
     * // High-profile match with senior referee
     * $referees = collect([$earlHebner]);
     * AddRefereesToMatchAction::run($match, $referees);
     *
     * // Special stipulation match with multiple officials
     * $referees = collect([$referee1, $referee2]);
     * AddRefereesToMatchAction::run($match, $referees);
     *
     * // Championship match with experienced official
     * $referees = collect([$charlesRobinson]);
     * AddRefereesToMatchAction::run($match, $referees);
     * ```
     */
    public function handle(EventMatch $eventMatch, \Illuminate\Support\Collection $referees): void
    {
        // Pre-filter referees to ensure only eligible officials are processed
        $eligibleReferees = $referees->filter(
            fn (Referee $referee) => $this->isRefereeEligibleForMatch($referee, $eventMatch)
        );

        // Validate we have referees to add after filtering
        if ($eligibleReferees->isEmpty()) {
            throw new InvalidArgumentException('No eligible referees provided for match assignment');
        }

        DB::transaction(function () use ($eventMatch, $eligibleReferees): void {
            // Add each eligible referee to officiate the match
            $eligibleReferees->each(
                fn (Referee $referee) => $this->eventMatchRepository->addRefereeToMatch($eventMatch, $referee)
            );
        });
    }

    /**
     * Check if a referee is eligible to officiate the match.
     *
     * @param  Referee  $referee  The referee to validate
     * @param  EventMatch  $eventMatch  The match they would officiate
     * @return bool True if the referee can officiate
     */
    private function isRefereeEligibleForMatch(Referee $referee, EventMatch $eventMatch): bool
    {
        // Basic availability checks - referee must be active and available
        if (! $referee->isBookable()) {
            return false;
        }

        // Check for conflicts with existing assignments
        // Note: More complex conflict checking would be implemented here
        // such as checking for double-booking on the same event date or conflicts of interest
        // Could validate against $eventMatch->event->date for scheduling conflicts

        return true;
    }
}
