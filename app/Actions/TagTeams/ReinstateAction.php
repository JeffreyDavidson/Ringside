<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Concerns\ReinstatementCascadeStrategy;
use App\Actions\Concerns\StatusTransitionPipeline;
use App\Exceptions\Roster\CannotBeReinstatedException;
use App\Models\TagTeams\TagTeam;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;

class ReinstateAction
{
    /**
     * Reinstate a suspended tag team.
     *
     * This handles the complete tag team reinstatement workflow using StatusTransitionPipeline:
     * - Validates the tag team can be reinstated (currently suspended)
     * - Uses StatusTransitionPipeline to properly end suspension and restore active status
     * - Automatically cascades reinstatement to suspended wrestlers and managers
     * - Makes the team available for match bookings and championships again
     * - Maintains transaction boundaries and error handling through pipeline
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline with ReinstatementCascadeStrategy for consistency
     * with other entity status transitions (wrestlers, managers, etc.)
     *
     * @param  TagTeam  $tagTeam  The tag team to reinstate
     * @param  Carbon|null  $reinstatementDate  The reinstatement date (defaults to now)
     * @throws CannotBeReinstatedException When tag team cannot be reinstated due to business rules
     *
     * @example
     * ```php
     * // Reinstate tag team immediately
     * $tagTeam = TagTeam::where('name', 'The Usos')->first();
     * resolve(ReinstateAction::class)->handle($tagTeam);
     *
     * // Reinstate with specific date
     * resolve(ReinstateAction::class)->handle($tagTeam, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(TagTeam $tagTeam, ?Carbon $reinstatementDate = null): void
    {
        $reinstatementDate = DateHelper::resolveDate($reinstatementDate);

        StatusTransitionPipeline::reinstate($tagTeam, $reinstatementDate)
            ->withCascade(ReinstatementCascadeStrategy::wrestlers())
            ->withCascade(ReinstatementCascadeStrategy::managers())
            ->execute();
    }
}
