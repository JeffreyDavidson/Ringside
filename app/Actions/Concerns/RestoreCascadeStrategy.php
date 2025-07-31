<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Restore cascade strategies for automatic relationship-based actions during restoration.
 *
 * This class provides strategies for handling relationships when a soft-deleted entity
 * is restored. The restore operation is unique in that it doesn't follow the typical
 * StatusTransitionPipeline pattern since it's not a status transition but a record restoration.
 *
 * BUSINESS CONTEXT:
 * When a tag team is restored from soft deletion:
 * - The team record is undeleted and becomes available again
 * - Former members may have joined other teams during the deletion period
 * - Force reunion may be required to rebuild the original team
 * - Employment relationships are not automatically restored to avoid conflicts
 *
 * DESIGN PATTERN:
 * Strategy pattern - each method returns a callable strategy that can be used
 * for post-restoration relationship management.
 *
 * @example
 * ```php
 * // After restoring a tag team
 * RestoreCascadeStrategy::forceReunion()($tagTeam, now(), 'restore');
 * ```
 */
class RestoreCascadeStrategy
{
    /**
     * Strategy to force former members out of current teams to reunite the restored team.
     *
     * This is an aggressive strategy that removes wrestlers from their current teams
     * to rebuild the original partnership. Use with caution as it affects other teams.
     *
     * @return callable Strategy function for forced team reunion
     */
    public static function forceReunion(): callable
    {
        return function (Model $entity, Carbon $date, string $operation): void {
            // Only execute on restore operations
            if ($operation !== 'restore') {
                return;
            }

            // Check if entity has wrestler relationships and necessary methods
            if (! method_exists($entity, 'wrestlers')) {
                return;
            }

            // Get all former wrestlers (including through soft-deleted relationships)
            $formerWrestlers = $entity->wrestlers()->withTrashed()->get();

            // Force each former wrestler out of their current teams
            foreach ($formerWrestlers as $wrestler) {
                if ($wrestler instanceof Wrestler) {
                    // End current team memberships for this wrestler
                    $wrestler->tagTeams()
                        ->wherePivot('left_at', null)
                        ->updateExistingPivot('*', ['left_at' => $date]);
                }
            }
        };
    }

    /**
     * Strategy to gently restore available members without forcing them from current teams.
     *
     * This strategy only restores former members who are currently not in other teams,
     * preserving existing commitments while rebuilding what's possible.
     *
     * @return callable Strategy function for gentle team restoration
     */
    public static function gentleReunion(): callable
    {
        return function (Model $entity, Carbon $date, string $operation): void {
            // Only execute on restore operations
            if ($operation !== 'restore') {
                return;
            }

            // Check if entity has wrestler relationships and necessary methods
            if (! method_exists($entity, 'wrestlers') || ! method_exists($entity, 'currentWrestlers')) {
                return;
            }

            // Get former wrestlers who are not currently in other teams
            $formerWrestlers = $entity->wrestlers()->withTrashed()->get();
            $availableWrestlers = $formerWrestlers->filter(function (Wrestler $wrestler) {
                // Check if wrestler is not currently in any team
                return $wrestler->currentTagTeams()->count() === 0;
            });

            // Note: In this gentle approach, we would typically restore pivot relationships
            // but since we're dealing with soft-deleted relationships, this might require
            // more complex logic that depends on your specific pivot table structure.
            // For now, this serves as a placeholder for the gentler reunion strategy.
        };
    }

    /**
     * Conditional strategy to handle reunion based on force parameter.
     *
     * This allows for dynamic cascade behavior based on the forceReunite parameter.
     *
     * @param  bool  $shouldForceReunion  Whether to force reunion or use gentle approach
     * @return callable Strategy function for conditional reunion
     */
    public static function conditionalReunion(bool $shouldForceReunion): callable
    {
        return function (Model $entity, Carbon $date, string $operation) use ($shouldForceReunion): void {
            // Only execute on restore operations
            if ($operation !== 'restore') {
                return;
            }

            if ($shouldForceReunion) {
                static::forceReunion()($entity, $date, $operation);
            } else {
                static::gentleReunion()($entity, $date, $operation);
            }
        };
    }
}
