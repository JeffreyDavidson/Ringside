<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Exceptions\Roster\CannotBeUnretiredException;
use App\Models\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction
{
    use AsAction;

    /**
     * Unretire a retired manager and return them to active talent management.
     *
     * This handles the complete manager unretirement workflow:
     * - Uses StatusTransitionPipeline for consistent unretirement handling
     * - Validates the manager can be unretired (currently retired)
     * - Ends the current retirement period with the specified date
     * - Creates a new employment record starting from the unretirement date
     * - Restores the manager to available status for wrestler and tag team assignments
     * - Preserves all historical retirement and employment records
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline for consistent status handling, following the same
     * pattern as other manager actions.
     *
     * @param  Manager  $manager  The manager to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     * @throws CannotBeUnretiredException When manager cannot be unretired due to business rules
     *
     * @example
     * ```php
     * // Unretire manager immediately
     * UnretireAction::run($manager);
     *
     * // Unretire with specific date
     * UnretireAction::run($manager, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $unretiredDate = null): void
    {
        $manager->ensureCanBeUnretired();

        $unretiredDate = DateHelper::resolveDate($unretiredDate);

        // Use StatusTransitionPipeline for consistent unretirement handling
        StatusTransitionPipeline::unretire($manager, $unretiredDate)->execute();

        // Restart employment from the unretirement date so the manager is
        // available for wrestler/tag team assignments again.
        $manager->employments()->create([
            'started_at' => $unretiredDate,
            'ended_at' => null,
        ]);
    }
}
