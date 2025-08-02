<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Actions\Concerns\Cascades\ManagerReleaseCascadeStrategy;
use App\Actions\Concerns\StatusTransitionPipeline;
use App\Exceptions\Roster\CannotBeReleasedException;
use App\Models\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction
{
    use AsAction;

    /**
     * Release a manager from employment and end all current relationships.
     *
     * This handles the complete manager release workflow with cascading effects:
     * - Uses StatusTransitionPipeline for consistent release handling
     * - Validates the manager can be released (currently employed)
     * - Uses ManagerReleaseCascadeStrategy to end management relationships
     * - Ends suspension, injury, and employment through pipeline
     * - Maintains all historical records for tracking purposes
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline with cascade strategies for comprehensive
     * release handling, following the same pattern as other entity types.
     *
     * @param  Manager  $manager  The manager to release
     * @param  Carbon|null  $releaseDate  The release date (defaults to now)
     * @throws CannotBeReleasedException When manager cannot be released due to business rules
     *
     * @example
     * ```php
     * // Release manager immediately
     * ReleaseAction::run($manager);
     *
     * // Release with specific date
     * ReleaseAction::run($manager, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $releaseDate = null): void
    {
        $manager->ensureCanBeReleased();

        $releaseDate = DateHelper::resolveDate($releaseDate);

        // Use StatusTransitionPipeline with cascade strategy for comprehensive release handling
        StatusTransitionPipeline::release($manager, $releaseDate)
            ->withCascade(ManagerReleaseCascadeStrategy::comprehensive())
            ->execute();
    }
}
