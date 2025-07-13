<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Managers\Manager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Retire a manager and end their management career.
     *
     * This handles the complete manager retirement workflow with cascading effects:
     * - Validates the manager can be retired (currently employed/active)
     * - Ends current wrestler and tag team management relationships
     * - Ends suspension and injury if active
     * - Ends employment period if currently employed
     * - Creates retirement record to formally end their management career
     * - Makes the manager unavailable for future talent management
     * - Preserves all historical records and relationships
     *
     * @param  Manager  $manager  The manager to retire
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     *
     * @throws CannotBeRetiredException When manager cannot be retired due to business rules
     *
     * @example
     * ```php
     * // Retire manager immediately
     * RetireAction::run($manager);
     *
     * // Retire with specific date
     * RetireAction::run($manager, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $retirementDate = null): void
    {
        $manager->ensureCanBeRetired();

        $retirementDate = $this->getEffectiveDate($retirementDate);

        DB::transaction(function () use ($manager, $retirementDate): void {
            // Handle manager status - only employed managers can have suspension/injury to end
            if ($manager->isEmployed()) {
                // End suspension or injury if active (employed manager cannot be both)
                if ($manager->isSuspended()) {
                    $this->managerRepository->endSuspension($manager, $retirementDate);
                } elseif ($manager->isInjured()) {
                    $this->managerRepository->endInjury($manager, $retirementDate);
                }

                // End employment
                $this->managerRepository->endEmployment($manager, $retirementDate);
            }

            // End current management relationships
            // Note: Management relationships are handled automatically by the repository

            // Create the retirement record to formally end their management career
            $this->managerRepository->createRetirement($manager, $retirementDate);
        });
    }
}
