<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Actions\Concerns\WrestlerRetirementCascadeStrategy;
use App\Exceptions\Roster\CannotBeRetiredException;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction
{
    use AsAction;

    /**
     * Retire a wrestler and end their career.
     *
     * This handles the complete wrestler retirement workflow using StatusTransitionPipeline:
     * - Validates the wrestler can be retired through pipeline validation
     * - Uses StatusTransitionPipeline to properly handle retirement status transition
     * - Automatically ends employment, suspension, and injury through pipeline
     * - Cascades to end all professional relationships (partnerships, memberships, etc.)
     * - Creates retirement record and updates status through pipeline
     * - Makes the wrestler permanently unavailable for competition
     * - Maintains transaction boundaries and error handling through pipeline
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline with WrestlerRetirementCascadeStrategy for consistency
     * with other entity retirement operations and comprehensive relationship management.
     *
     * @param  Wrestler  $wrestler  The wrestler to retire
     * @param  Carbon|null  $retirementDate  The retirement start date (defaults to now)
     * @throws CannotBeRetiredException When wrestler cannot be retired due to business rules
     *
     * @example
     * ```php
     * // Retire wrestler immediately
     * RetireAction::run($wrestler);
     *
     * // Retire with specific start date
     * RetireAction::run($wrestler, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Wrestler $wrestler, ?Carbon $retirementDate = null): void
    {
        $retirementDate = DateHelper::resolveDate($retirementDate);

        StatusTransitionPipeline::retire($wrestler, $retirementDate)
            ->withCascade(WrestlerRetirementCascadeStrategy::endAllRelationships())
            ->execute();
    }
}
