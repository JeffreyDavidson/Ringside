<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Managers\EmployAction as ManagersEmployAction;
use App\Exceptions\Status\CannotBeUnretiredException;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Action for unretiring a wrestler and restoring them to active status.
 *
 * This action handles the complete workflow for bringing a wrestler out of retirement,
 * including ending their current retirement record and creating a new employment record.
 * It ensures that only retired wrestlers can be unretired and maintains data integrity
 * throughout the process.
 *
 * The action follows these business rules:
 * - Only wrestlers who are currently retired can be unretired
 * - Unretiring automatically creates a new employment record
 * - Uses the current date if no specific unretirement date is provided
 * - Maintains retirement history by ending the current retirement rather than deleting it
 *
 * @see Wrestler For the wrestler model
 * @see ManagesRetirement For retirement management
 * @see ManagesEmployment For employment management
 *
 * @example
 * ```php
 * $wrestler = Wrestler::find(1);
 *
 * // Unretire with current date
 * UnretireAction::run($wrestler);
 *
 * // Unretire with specific date
 * UnretireAction::run($wrestler, Carbon::parse('2024-06-01'));
 * ```
 */
class UnretireAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Create a new unretire action instance.
     */
    public function __construct(
        WrestlerRepository $wrestlerRepository,
        protected ManagersEmployAction $managersEmployAction
    ) {
        parent::__construct($wrestlerRepository);
    }

    /**
     * Unretire a wrestler and make them available for employment.
     *
     * This handles the complete wrestler unretirement workflow:
     * - Validates the wrestler can be unretired (currently retired)
     * - Ends the current retirement record
     * - Makes the wrestler available for new employment opportunities
     *
     * @param  Wrestler  $wrestler  The wrestler to unretire
     * @param  Carbon|null  $unretirementDate  The unretirement date (defaults to now)
     *
     * @throws CannotBeUnretiredException When wrestler cannot be unretired due to business rules
     *
     * @example
     * ```php
     * // Unretire wrestler immediately
     * UnretireAction::run($wrestler);
     *
     * // Unretire with specific date
     * UnretireAction::run($wrestler, Carbon::parse('2024-01-15'));
     * ```
     */
    public function handle(Wrestler $wrestler, ?Carbon $unretirementDate = null): void
    {
        // Validate business rules before proceeding
        $wrestler->ensureCanBeUnretired();

        $unretirementDate = $this->getEffectiveDate($unretirementDate);

        $this->wrestlerRepository->endRetirement($wrestler, $unretirementDate);
    }
}
