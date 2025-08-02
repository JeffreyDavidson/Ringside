<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Exceptions\Roster\CannotBeInjuredException;
use App\Models\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class InjureAction
{
    use AsAction;

    /**
     * Record a manager injury.
     *
     * This handles the complete manager injury workflow:
     * - Uses StatusTransitionPipeline for consistent injury handling
     * - Validates the manager can be injured (currently employed, not already injured)
     * - Creates an injury record with the specified start date
     * - Temporarily removes the manager from active wrestler/tag team management duties
     * - Maintains employment status while marking as unavailable due to injury
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline for consistent status handling, following the same
     * pattern as other manager actions.
     *
     * @param  Manager  $manager  The manager to mark as injured
     * @param  Carbon|null  $injureDate  The injury date (defaults to now)
     * @throws CannotBeInjuredException When manager cannot be injured due to business rules
     *
     * @example
     * ```php
     * // Mark manager as injured immediately
     * InjureAction::run($manager);
     *
     * // Record injury with specific date
     * InjureAction::run($manager, Carbon::parse('2024-01-15'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $injureDate = null): void
    {
        $manager->ensureCanBeInjured();

        $injureDate = DateHelper::resolveDate($injureDate);

        // Use StatusTransitionPipeline for consistent injury handling
        StatusTransitionPipeline::injure($manager, $injureDate)->execute();
    }
}
