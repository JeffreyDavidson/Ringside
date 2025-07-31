<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Actions\Concerns\SuspensionCascadeStrategy;
use App\Models\TagTeams\TagTeam;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction
{
    use AsAction;

    /**
     * Suspend a tag team.
     *
     * This handles the complete tag team suspension workflow using StatusTransitionPipeline:
     * - Validates the tag team can be suspended (currently employed, not already suspended)
     * - Uses StatusTransitionPipeline to properly create suspension record
     * - Automatically cascades suspension to eligible wrestlers and managers
     * - Temporarily removes the tag team from active competition
     * - Maintains employment status while restricting availability
     * - Ensures all members are properly suspended to maintain team suspension integrity
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline with SuspensionCascadeStrategy for consistency
     * with other entity status transitions and proper member suspension management.
     *
     * @param  TagTeam  $tagTeam  The tag team to suspend
     * @param  Carbon|null  $suspensionDate  The suspension start date (defaults to now)
     *
     * @example
     * ```php
     * // Suspend tag team immediately
     * $tagTeam = TagTeam::where('name', 'D-Generation X')->first();
     * SuspendAction::run($tagTeam);
     *
     * // Schedule suspension for future date
     * SuspendAction::run($tagTeam, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(TagTeam $tagTeam, ?Carbon $suspensionDate = null): void
    {
        $suspensionDate = DateHelper::resolveDate($suspensionDate);

        StatusTransitionPipeline::suspend($tagTeam, $suspensionDate)
            ->withCascade(SuspensionCascadeStrategy::allMembers())
            ->execute();
    }
}
