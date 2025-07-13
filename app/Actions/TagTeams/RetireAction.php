<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Concerns\UnifiedRetireAction;
use App\Models\TagTeams\TagTeam;
use Exception;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Retire a tag team and end their partnership.
     *
     * This handles the complete tag team retirement workflow using the UnifiedRetireAction:
     * - Validates the tag team can be retired (currently employed/active)
     * - Ends current wrestler partnerships (wrestlers may continue as singles)
     * - Ends current manager relationships
     * - Ends suspension if active
     * - Ends employment period if currently employed
     * - Creates retirement record to formally end the tag team partnership
     * - Makes the tag team permanently unavailable for competition
     * - Preserves all historical records and championship lineage
     * - Individual members may continue their careers independently
     *
     * @param  TagTeam  $tagTeam  The tag team to retire
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     *
     * @throws Exception When tag team cannot be retired due to business rules
     *
     * @example
     * ```php
     * // Retire tag team immediately
     * $tagTeam = TagTeam::where('name', 'The Undertakers')->first();
     * RetireAction::run($tagTeam);
     *
     * // Retire with specific date
     * RetireAction::run($tagTeam, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(TagTeam $tagTeam, ?Carbon $retirementDate = null): void
    {
        UnifiedRetireAction::run($tagTeam, $retirementDate);
    }
}
