<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Exceptions\Roster\CannotBeReinstatedException;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction
{
    use AsAction;

    /**
     * Reinstate a wrestler and make them available for employment.
     *
     * This handles the complete wrestler reinstatement workflow using StatusTransitionPipeline:
     * - Validates the wrestler can be reinstated through pipeline validation
     * - Uses StatusTransitionPipeline to properly end suspension and injury status
     * - Maintains transaction boundaries and error handling through pipeline
     * - Makes the wrestler available for new employment opportunities
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline for consistency with other entity reinstatement operations
     * and proper status transition management.
     *
     * @param  Wrestler  $wrestler  The wrestler to reinstate
     * @param  Carbon|null  $reinstatementDate  The reinstatement date (defaults to now)
     * @throws CannotBeReinstatedException When wrestler cannot be reinstated due to business rules
     *
     * @example
     * ```php
     * // Reinstate wrestler immediately
     * ReinstateAction::run($wrestler);
     *
     * // Reinstate with specific date
     * ReinstateAction::run($wrestler, Carbon::parse('2024-01-15'));
     * ```
     */
    public function handle(Wrestler $wrestler, ?Carbon $reinstatementDate = null): void
    {
        $wrestler->ensureCanBeReinstated();

        $reinstatementDate = DateHelper::resolveDate($reinstatementDate);

        StatusTransitionPipeline::reinstate($wrestler, $reinstatementDate)->execute();
    }
}
