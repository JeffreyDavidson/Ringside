<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Concerns\EmploymentCascadeStrategy;
use App\Actions\Concerns\StatusTransitionPipeline;
use App\Models\TagTeams\TagTeam;
use App\Support\DateHelper;
use Exception;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction
{
    use AsAction;

    /**
     * Employ a tag team using the StatusTransitionPipeline.
     *
     * This handles the complete tag team employment workflow using the StatusTransitionPipeline:
     * - Validates the tag team can be employed (not retired, not already employed)
     * - Ends retirement if currently retired
     * - Creates an employment record for the tag team
     * - Employs all current wrestlers through cascading
     * - Employs all current managers through cascading
     * - Makes the tag team available for match bookings and championships
     *
     * @param  TagTeam  $tagTeam  The tag team to employ
     * @param  Carbon|null  $employmentDate  The employment start date (defaults to now)
     * @throws Exception When tag team cannot be employed due to business rules
     *
     * @example
     * ```php
     * // Employ tag team immediately
     * $tagTeam = TagTeam::where('name', 'The Young Bucks')->first();
     * EmployAction::run($tagTeam);
     *
     * // Employ with specific start date
     * EmployAction::run($tagTeam, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(TagTeam $tagTeam, ?Carbon $employmentDate = null): void
    {
        $employmentDate = DateHelper::resolveDate($employmentDate);

        StatusTransitionPipeline::employ($tagTeam, $employmentDate)
            ->withCascade(EmploymentCascadeStrategy::wrestlers())
            ->withCascade(EmploymentCascadeStrategy::managers())
            ->execute();
    }
}
