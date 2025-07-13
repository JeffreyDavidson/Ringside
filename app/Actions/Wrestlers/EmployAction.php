<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Concerns\EmploymentCascadeStrategy;
use App\Actions\Concerns\StatusTransitionPipeline;
use App\Models\Wrestlers\Wrestler;
use Exception;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Employ a wrestler and activate their career.
     *
     * This handles the complete wrestler employment workflow using the StatusTransitionPipeline:
     * - Validates the wrestler can be employed (not retired, not already employed)
     * - Prepares the wrestler by ending any active suspension or injury status
     * - Creates an employment record with the specified start date
     * - Employs any current managers who are not yet employed through cascading
     * - Makes the wrestler available for match bookings and storylines
     *
     * @param  Wrestler  $wrestler  The wrestler to employ
     * @param  Carbon|null  $employmentDate  The employment start date (defaults to now)
     *
     * @throws Exception When wrestler cannot be employed due to business rules
     *
     * @example
     * ```php
     * // Employ wrestler immediately
     * EmployAction::run($wrestler);
     *
     * // Employ with specific start date
     * EmployAction::run($wrestler, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(Wrestler $wrestler, ?Carbon $employmentDate = null): void
    {
        // Validate business rules before proceeding
        $wrestler->ensureCanBeEmployed();

        StatusTransitionPipeline::employ($wrestler, $employmentDate)
            ->withCascade(EmploymentCascadeStrategy::managers())
            ->execute();
    }
}
