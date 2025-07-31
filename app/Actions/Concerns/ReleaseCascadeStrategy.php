<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use App\Models\Managers\Manager;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Release cascade strategies for automatic relationship-based actions during release.
 *
 * This class provides strategies for handling relationships when an entity is released
 * from employment. Unlike retirement (which may cascade member retirement), release
 * typically ends partnerships and management relationships without affecting individual
 * member employment status.
 *
 * BUSINESS CONTEXT:
 * When a tag team is released:
 * - The partnership dissolves (wrestlers become free agents)
 * - Manager relationships end
 * - Individual members retain their employment status and can form new partnerships
 * - This is different from retirement where members might also retire
 *
 * DESIGN PATTERN:
 * Strategy pattern - each method returns a callable strategy that can be used
 * with StatusTransitionPipeline.withCascade().
 *
 * @example
 * ```php
 * // Release a tag team and end all partnerships/manager relationships
 * StatusTransitionPipeline::release($tagTeam, $date)
 *     ->withCascade(ReleaseCascadeStrategy::endPartnerships())
 *     ->withCascade(ReleaseCascadeStrategy::endManagerRelationships())
 *     ->execute();
 * ```
 */
class ReleaseCascadeStrategy
{
    /**
     * Strategy to end all current wrestler partnerships.
     *
     * When a tag team is released, the partnership dissolves but wrestlers
     * retain their individual employment status and can form new partnerships.
     *
     * @return callable Strategy function for ending wrestler partnerships
     */
    public static function endPartnerships(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on release transitions
            if ($transition !== 'release') {
                return;
            }

            // Check if entity has wrestler relationships (tag teams)
            if (! method_exists($entity, 'currentWrestlers') || ! method_exists($entity, 'wrestlers')) {
                return;
            }

            // End all current wrestler partnerships
            $entity->currentWrestlers->each(function (Wrestler $wrestler) use ($entity, $date) {
                $entity->wrestlers()->updateExistingPivot($wrestler->id, [
                    'left_at' => $date,
                ]);
            });
        };
    }

    /**
     * Strategy to end all current manager relationships.
     *
     * When a tag team is released, manager relationships end but managers
     * retain their employment status and can manage other talent.
     *
     * @return callable Strategy function for ending manager relationships
     */
    public static function endManagerRelationships(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on release transitions
            if ($transition !== 'release') {
                return;
            }

            // Check if entity has manager relationships
            if (! method_exists($entity, 'currentManagers') || ! method_exists($entity, 'managers')) {
                return;
            }

            // End all current manager relationships
            $entity->currentManagers->each(function (Manager $manager) use ($entity, $date) {
                $entity->managers()->updateExistingPivot($manager->id, [
                    'fired_at' => $date,
                ]);
            });
        };
    }

    /**
     * Combined strategy to end all relationships (partnerships and management).
     *
     * This represents a complete relationship dissolution when the entity is released.
     *
     * @return callable Strategy function for ending all relationships
     */
    public static function endAllRelationships(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on release transitions
            if ($transition !== 'release') {
                return;
            }

            // Execute individual strategies
            static::endPartnerships()($entity, $date, $transition);
            static::endManagerRelationships()($entity, $date, $transition);
        };
    }
}
