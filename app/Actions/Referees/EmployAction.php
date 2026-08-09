<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Lifecycle\EmploymentPeriodManager;
use App\Models\Referees\Referee;
use App\Support\DateHelper;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmployAction
{
    public function __construct(private readonly EmploymentPeriodManager $employmentPeriods) {}

    /**
     * Employ a referee.
     *
     * This handles the complete referee employment workflow:
     * - Validates the referee can be employed (not retired, not already employed)
     * - Ends retirement if currently retired
     * - Creates the employment record through the shared lifecycle component
     * - Makes the referee available for match officiating assignments
     *
     * @param  Referee  $referee  The referee to employ
     * @param  Carbon|null  $employmentDate  The employment start date (defaults to now)
     * @throws Exception When referee cannot be employed due to business rules
     */
    public function handle(Referee $referee, ?Carbon $employmentDate = null): void
    {
        $referee->ensureCanBeEmployed();

        $employmentDate = DateHelper::resolveDate($employmentDate);

        DB::transaction(function () use ($referee, $employmentDate): void {
            if ($referee->isRetired()) {
                $referee->currentRetirement()->update(['ended_at' => $employmentDate]);
            }

            $this->employmentPeriods->start($referee, $employmentDate);
        });
    }
}
