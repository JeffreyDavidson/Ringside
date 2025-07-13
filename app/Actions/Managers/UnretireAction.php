<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Status\CannotBeUnretiredException;
use App\Models\Managers\Manager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Unretire a retired manager and return them to active talent management.
     *
     * This handles the complete manager unretirement workflow:
     * - Validates the manager can be unretired (currently retired)
     * - Ends the current retirement period with the specified date
     * - Creates a new employment record starting from the unretirement date
     * - Restores the manager to available status for wrestler and tag team assignments
     * - Preserves all historical retirement and employment records
     *
     * @param  Manager  $manager  The manager to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     *
     * @throws CannotBeUnretiredException When manager cannot be unretired due to business rules
     *
     * @example
     * ```php
     * // Unretire manager immediately
     * UnretireAction::run($manager);
     *
     * // Unretire with specific date
     * UnretireAction::run($manager, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $unretiredDate = null): void
    {
        $manager->ensureCanBeUnretired();

        $unretiredDate = $this->getEffectiveDate($unretiredDate);

        DB::transaction(function () use ($manager, $unretiredDate): void {
            // End the current retirement record
            $this->managerRepository->endRetirement($manager, $unretiredDate);

            // Create a new employment record starting from the unretirement date
            $this->managerRepository->createEmployment($manager, $unretiredDate);
        });
    }
}
