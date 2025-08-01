<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Actions\Concerns\WrestlerRetirementCascadeStrategy;
use App\Exceptions\Roster\CannotBeReleasedException;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction
{
    use AsAction;

    /**
     * Release a wrestler from employment and end all current relationships.
     *
     * This handles the complete wrestler release workflow using StatusTransitionPipeline:
     * - Validates the wrestler can be released through pipeline validation
     * - Uses StatusTransitionPipeline to properly handle employment termination
     * - Automatically ends suspension and injury if active through pipeline
     * - Cascades to end all professional relationships (same as retirement pattern)
     * - Maintains all historical records for tracking purposes
     * - Maintains transaction boundaries and error handling through pipeline
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline with WrestlerRetirementCascadeStrategy for consistency.
     * Release follows the same relationship-ending pattern as retirement since both
     * terminate all professional relationships.
     *
     * @param  Wrestler  $wrestler  The wrestler to release
     * @param  Carbon|null  $releaseDate  The release date (defaults to now)
     * @throws CannotBeReleasedException When wrestler cannot be released due to business rules
     *
     * @example
     * ```php
     * // Release wrestler immediately
     * ReleaseAction::run($wrestler);
     *
     * // Release with specific date
     * ReleaseAction::run($wrestler, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Wrestler $wrestler, ?Carbon $releaseDate = null): void
    {
        $wrestler->ensureCanBeReleased();

        $releaseDate = DateHelper::resolveDate($releaseDate);

        StatusTransitionPipeline::release($wrestler, $releaseDate)
            ->withCascade(WrestlerRetirementCascadeStrategy::endAllRelationships())
            ->execute();
    }
}
