<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Models\Referees\Referee;
use Exception;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Employ a referee.
     *
     * This handles the complete referee employment workflow using the StatusTransitionPipeline:
     * - Validates the referee can be employed (not retired, not already employed)
     * - Ends retirement if currently retired
     * - Creates an employment record with the specified start date
     * - Makes the referee available for match officiating assignments
     *
     * @param  Referee  $referee  The referee to employ
     * @param  Carbon|null  $employmentDate  The employment start date (defaults to now)
     *
     * @throws Exception When referee cannot be employed due to business rules
     *
     * @example
     * ```php
     * // Employ referee immediately
     * EmployAction::run($referee);
     *
     * // Employ with specific start date
     * EmployAction::run($referee, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(Referee $referee, ?Carbon $employmentDate = null): void
    {
        StatusTransitionPipeline::employ($referee, $employmentDate)->execute();
    }
}
