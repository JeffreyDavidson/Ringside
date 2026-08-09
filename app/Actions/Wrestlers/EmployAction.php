<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Concerns\EmploymentCascadeStrategy;
use App\Lifecycle\EmploymentPeriodManager;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmployAction
{
    public function __construct(private readonly EmploymentPeriodManager $employmentPeriods) {}

    /**
     * Employ a wrestler and activate their career.
     *
     * This handles the complete wrestler employment workflow:
     * - Validates the wrestler can be employed (not retired, not already employed)
     * - Prepares the wrestler by ending any active suspension or injury status
     * - Creates the employment record through the shared lifecycle component
     * - Employs any current managers who are not yet employed through cascading
     * - Makes the wrestler available for match bookings and storylines
     *
     * @param  Wrestler  $wrestler  The wrestler to employ
     * @param  Carbon|null  $employmentDate  The employment start date (defaults to now)
     * @throws Exception When wrestler cannot be employed due to business rules
     */
    public function handle(Wrestler $wrestler, ?Carbon $employmentDate = null): void
    {
        $wrestler->ensureCanBeEmployed();

        $employmentDate = DateHelper::resolveDate($employmentDate);

        DB::transaction(function () use ($wrestler, $employmentDate): void {
            if ($wrestler->isRetired()) {
                $wrestler->currentRetirement()->update(['ended_at' => $employmentDate]);
            }

            $this->employmentPeriods->start($wrestler, $employmentDate);
            EmploymentCascadeStrategy::managers()($wrestler, $employmentDate, 'employ');
        });
    }
}
