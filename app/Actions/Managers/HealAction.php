<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Exceptions\Roster\CannotBeClearedFromInjuryException;
use App\Models\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class HealAction
{
    use AsAction;

    /**
     * Heal a manager from injury and return them to active management.
     *
     * This handles the complete injury recovery workflow:
     * - Uses StatusTransitionPipeline for consistent injury healing
     * - Validates the manager can be healed from injury (currently injured)
     * - Ends the current injury period with the specified recovery date
     * - Restores the manager to active talent management duties
     * - Makes the manager available for wrestler and tag team assignments again
     * - Preserves injury history for medical and administrative records
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline for consistent status handling, following the same
     * pattern as other manager actions for consistency.
     *
     * @param  Manager  $manager  The injured manager to heal
     * @param  Carbon|null  $recoveryDate  The recovery date (defaults to now)
     * @throws CannotBeClearedFromInjuryException When manager cannot be healed due to business rules
     *
     * @example
     * ```php
     * // Heal injury immediately
     * HealAction::run($manager);
     *
     * // Heal injury with specific recovery date
     * HealAction::run($manager, Carbon::parse('2024-02-01'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $recoveryDate = null): void
    {
        $manager->ensureCanBeHealed();

        $recoveryDate = DateHelper::resolveDate($recoveryDate);

        // Use StatusTransitionPipeline for consistent injury healing
        StatusTransitionPipeline::heal($manager, $recoveryDate)->execute();
    }
}
