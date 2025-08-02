<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Actions\Concerns\Cascades\ManagerRetirementCascadeStrategy;
use App\Actions\Concerns\StatusTransitionPipeline;
use App\Exceptions\Roster\CannotBeRetiredException;
use App\Models\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction
{
    use AsAction;

    /**
     * Retire a manager and end their management career.
     *
     * This handles the complete manager retirement workflow with cascading effects:
     * - Uses StatusTransitionPipeline for consistent retirement handling
     * - Validates the manager can be retired (currently employed/active)
     * - Uses ManagerRetirementCascadeStrategy to end management relationships
     * - Ends suspension, injury, and employment through pipeline
     * - Creates retirement record to formally end their management career
     * - Makes the manager unavailable for future talent management
     * - Preserves all historical records and relationships
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline with cascade strategies for comprehensive
     * retirement handling, following the same pattern as other entity types.
     *
     * @param  Manager  $manager  The manager to retire
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     * @throws CannotBeRetiredException When manager cannot be retired due to business rules
     *
     * @example
     * ```php
     * // Retire manager immediately
     * RetireAction::run($manager);
     *
     * // Retire with specific date
     * RetireAction::run($manager, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $retirementDate = null): void
    {
        $manager->ensureCanBeRetired();

        $retirementDate = DateHelper::resolveDate($retirementDate);

        // Use StatusTransitionPipeline with cascade strategy for comprehensive retirement handling
        StatusTransitionPipeline::retire($manager, $retirementDate)
            ->withCascade(ManagerRetirementCascadeStrategy::comprehensive())
            ->execute();
    }
}
