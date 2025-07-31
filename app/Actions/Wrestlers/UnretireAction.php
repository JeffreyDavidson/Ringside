<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\CannotBeUnretiredException;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction
{
    use AsAction;

    /**
     * Create a new unretire action instance.
     */
    public function __construct(
        protected EmployAction $employAction
    ) {}

    /**
     * Unretire a wrestler and return them to active competition.
     *
     * This handles the complete wrestler comeback workflow with flexible employment options:
     * - Validates the wrestler can come out of retirement (business rule compliance)
     * - Ends the current retirement period with the specified date
     * - Updates status to unemployed (no longer retired, but not employed)
     * - Optionally employs the wrestler immediately or leaves unemployed for manual employment
     * - Restores the wrestler to available status for match bookings
     * - Makes the wrestler available for new career opportunities
     * - Preserves all historical retirement records
     *
     * ARCHITECTURAL PATTERN:
     * Uses EmployAction for consistent employment handling when requested.
     * Note: This doesn't use StatusTransitionPipeline as unretirement involves ending
     * retirement rather than starting a new status transition.
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

        DB::transaction(function () use ($wrestler, $unretirementDate, $employImmediately): void {
            // End the current retirement record
            $wrestler->retirements()->whereNull('ended_at')->update(['ended_at' => $unretirementDate]);

            // Update status to unemployed (no longer retired, but not employed)
            $wrestler->update(['status' => EmploymentStatus::Unemployed]);

            // Employ immediately if requested
            if ($employImmediately) {
                $this->employAction->handle($wrestler, $unretirementDate);
            }
        });
    }
}
