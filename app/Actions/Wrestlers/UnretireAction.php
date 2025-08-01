<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Actions\Concerns\WrestlerUnretirementCascadeStrategy;
use App\Exceptions\Roster\CannotBeUnretiredException;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction
{
    use AsAction;

    /**
     * Unretire a wrestler and return them to active competition.
     *
     * This handles the complete wrestler comeback workflow with flexible employment options:
     * - Validates the wrestler can come out of retirement (business rule compliance)
     * - Uses StatusTransitionPipeline to end the current retirement period
     * - Updates status to unemployed (no longer retired, but not employed)
     * - Optionally employs the wrestler immediately or leaves unemployed for manual employment
     * - Restores the wrestler to available status for match bookings
     * - Makes the wrestler available for new career opportunities
     * - Preserves all historical retirement records
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline for consistent status transition handling and
     * EmployAction for employment when requested.
     *
     * @param  Wrestler  $wrestler  The wrestler to unretire
     * @param  Carbon|null  $unretirementDate  The unretirement date (defaults to now)
     * @param  bool  $employImmediately  Whether to employ the wrestler immediately (default: true)
     * @throws CannotBeUnretiredException When wrestler cannot be unretired due to business rules
     *
     * @example
     * ```php
     * // Unretire wrestler and employ immediately
     * UnretireAction::run($wrestler);
     *
     * // Unretire with specific date
     * UnretireAction::run($wrestler, Carbon::parse('2024-01-15'));
     *
     * // Unretire without employing immediately (manual employment later)
     * UnretireAction::run($wrestler, employImmediately: false);
     * ```
     */
    public function handle(Wrestler $wrestler, ?Carbon $unretirementDate = null, bool $employImmediately = true): void
    {
        $wrestler->ensureCanBeUnretired();

        $unretirementDate = DateHelper::resolveDate($unretirementDate);

        $cascade = $employImmediately
            ? WrestlerUnretirementCascadeStrategy::withEmployment()
            : WrestlerUnretirementCascadeStrategy::withoutEmployment();

        StatusTransitionPipeline::unretire($wrestler, $unretirementDate)
            ->withCascade($cascade)
            ->execute();
    }
}
