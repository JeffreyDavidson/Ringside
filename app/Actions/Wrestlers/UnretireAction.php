<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Concerns\WrestlerUnretirementCascadeStrategy;
use App\Exceptions\Roster\CannotBeUnretiredException;
use App\Lifecycle\RetirementPeriodManager;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UnretireAction
{
    public function __construct(private readonly RetirementPeriodManager $retirementPeriods) {}

    /**
     * Unretire a wrestler and return them to active competition.
     *
     * This handles the complete wrestler comeback workflow with flexible employment options:
     * - Validates the wrestler can come out of retirement (business rule compliance)
     * - Ends the current retirement period through RetirementPeriodManager
     * - Optionally employs the wrestler immediately or leaves unemployed for manual employment
     * - Restores the wrestler to available status for match bookings
     * - Makes the wrestler available for new career opportunities
     * - Preserves all historical retirement records
     *
     * ARCHITECTURAL PATTERN:
     * Uses a selected WrestlerUnretirementCascadeStrategy for employment follow-up.
     *
     * @param  Wrestler  $wrestler  The wrestler to unretire
     * @param  Carbon|null  $unretirementDate  The unretirement date (defaults to now)
     * @param  bool  $employImmediately  Whether to employ the wrestler immediately (default: true)
     * @throws CannotBeUnretiredException When wrestler cannot be unretired due to business rules
     */
    public function handle(Wrestler $wrestler, ?Carbon $unretirementDate = null, bool $employImmediately = true): void
    {
        $wrestler->ensureCanBeUnretired();

        $unretirementDate = DateHelper::resolveDate($unretirementDate);

        $cascade = $employImmediately
            ? WrestlerUnretirementCascadeStrategy::withEmployment()
            : WrestlerUnretirementCascadeStrategy::withoutEmployment();

        DB::transaction(function () use ($wrestler, $unretirementDate, $cascade): void {
            $this->retirementPeriods->end($wrestler, $unretirementDate);
            $cascade($wrestler, $unretirementDate, 'unretire');
        });
    }
}
