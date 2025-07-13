<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Concerns\UnifiedSuspendAction;
use App\Models\TagTeams\TagTeam;
use Exception;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Suspend a tag team.
     *
     * This handles the complete tag team suspension workflow using the UnifiedSuspendAction:
     * - Validates the tag team can be suspended (currently employed, not already suspended)
     * - Creates a suspension record with the specified start date
     * - Suspends all current wrestlers and managers with the team
     * - Temporarily removes the tag team from active competition
     * - Maintains employment status while restricting availability
     *
     * @param  TagTeam  $tagTeam  The tag team to suspend
     * @param  Carbon|null  $suspensionDate  The suspension start date (defaults to now)
     *
     * @throws Exception When tag team cannot be suspended due to business rules
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
        UnifiedSuspendAction::run($tagTeam, $suspensionDate);
    }
}
