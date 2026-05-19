<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Concerns\RetirementCascadeStrategy;
use App\Actions\Concerns\StatusTransitionPipeline;
use App\Exceptions\Roster\TagTeams\CannotBeRetiredException;
use App\Models\TagTeams\TagTeam;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction
{
    use AsAction;

    /**
     * Retire a tag team and end their partnership.
     *
     * This handles the complete tag team retirement workflow using StatusTransitionPipeline:
     * - Validates the tag team can be retired (business rule compliance)
     * - Uses StatusTransitionPipeline to properly handle retirement status transition
     * - Automatically ends employment and suspension through pipeline
     * - Optionally cascades retirement to available partners and managers
     * - Creates retirement record and updates status through pipeline
     * - Makes the tag team permanently unavailable for competition
     * - Preserves all historical records and championship lineage
     * - Individual members may continue their careers independently
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline with RetirementCascadeStrategy for consistency
     * with other entity status transitions and flexible cascade behavior.
     *
     * @param  TagTeam  $tagTeam  The tag team to retire
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     * @param  bool  $retirePartners  Whether to retire available partners (default: true)
     * @throws CannotBeRetiredException When tag team cannot be retired due to business rules
     *
     * @example
     * ```php
     * // Retire tag team immediately with member retirement
     * $tagTeam = TagTeam::where('name', 'The Undertakers')->first();
     * RetireAction::run($tagTeam);
     *
     * // Retire with specific date
     * RetireAction::run($tagTeam, Carbon::parse('2024-12-31'));
     *
     * // Retire without retiring partners (partners continue independently)
     * RetireAction::run($tagTeam, retirePartners: false);
     * ```
     */
    public function handle(TagTeam $tagTeam, ?Carbon $retirementDate = null, bool $retirePartners = true): void
    {
        $retirementDate = DateHelper::resolveDate($retirementDate);

        StatusTransitionPipeline::retire($tagTeam, $retirementDate)
            ->withCascade(RetirementCascadeStrategy::conditionalMembers($retirePartners))
            ->execute();
    }
}
