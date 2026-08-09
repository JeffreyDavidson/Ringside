<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\RetirementPeriodManager;
use App\Models\Managers\Manager;
use App\Support\DateHelper;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmployAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly RetirementPeriodManager $retirementPeriods,
    ) {}

    /**
     * Employ a manager.
     *
     * This handles the complete manager employment workflow:
     * - Validates the manager can be employed (not retired, not already employed)
     * - Ends retirement if currently retired
     * - Creates the employment record through the shared lifecycle component
     * - Makes the manager available for talent management assignments
     *
     * @param  Manager  $manager  The manager to employ
     * @param  Carbon|null  $startDate  The employment start date (defaults to now)
     * @throws Exception When manager cannot be employed due to business rules
     */
    public function handle(Manager $manager, ?Carbon $startDate = null): void
    {
        $manager->ensureCanBeEmployed();

        $startDate = DateHelper::resolveDate($startDate);

        DB::transaction(function () use ($manager, $startDate): void {
            if ($manager->isRetired()) {
                $this->retirementPeriods->end($manager, $startDate);
            }

            $this->employmentPeriods->start($manager, $startDate);
        });
    }
}
