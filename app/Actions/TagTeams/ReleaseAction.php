<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Concerns\ReleaseCascadeStrategy;
use App\Actions\Concerns\StatusTransitionPipeline;
use App\Exceptions\Roster\CannotBeReleasedException;
use App\Models\TagTeams\TagTeam;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction
{
    use AsAction;

    /**
     * Release a tag team from employment and end all current relationships.
     *
     * This handles the complete tag team release workflow using StatusTransitionPipeline:
     * - Validates the tag team can be released (currently employed)
     * - Uses StatusTransitionPipeline to properly handle employment termination
     * - Automatically ends suspension if active through pipeline
     * - Cascades to end wrestler partnerships (wrestlers become free agents)
     * - Cascades to end manager relationships (managers remain available)
     * - Maintains all historical records for tracking purposes
     * - Individual members retain employment status and may form new partnerships
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline with ReleaseCascadeStrategy for consistency
     * with other entity status transitions and proper relationship management.
     *
     * @param  TagTeam  $tagTeam  The tag team to release
     * @param  Carbon|null  $releaseDate  The release date (defaults to now)
     * @throws CannotBeReleasedException When tag team cannot be released due to business rules
     *
     * @example
     * ```php
     * // Release tag team immediately
     * $tagTeam = TagTeam::where('name', 'The Shield')->first();
     * ReleaseAction::run($tagTeam);
     *
     * // Release with specific date
     * ReleaseAction::run($tagTeam, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(TagTeam $tagTeam, ?Carbon $releaseDate = null): void
    {
        $releaseDate = DateHelper::resolveDate($releaseDate);

        StatusTransitionPipeline::release($tagTeam, $releaseDate)
            ->withCascade(ReleaseCascadeStrategy::endAllRelationships())
            ->execute();
    }
}
