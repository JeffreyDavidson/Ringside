<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use App\Models\Managers\Manager;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Reinstatement cascade strategies for automatic relationship-based reinstatement.
 *
 * This class provides strategies for automatically reinstating related entities when
 * a primary entity is reinstated. Common cascading scenarios include reinstating
 * wrestlers and managers when a tag team is reinstated.
 *
 * BUSINESS CONTEXT:
 * When a tag team is reinstated from suspension, their suspended wrestlers and
 * managers should also be reinstated to restore the complete team unit.
 *
 * DESIGN PATTERN:
 * Strategy pattern - each method returns a callable strategy that can be used
 * with StatusTransitionPipeline.withCascade().
 *
 * @example
 * ```php
 * // Reinstate a tag team and automatically reinstate suspended members
 * StatusTransitionPipeline::reinstate($tagTeam, $date)
 *     ->withCascade(ReinstatementCascadeStrategy::wrestlers())
 *     ->withCascade(ReinstatementCascadeStrategy::managers())
 *     ->execute();
 * ```
 */
class ReinstatementCascadeStrategy
{
    /**
     * Strategy to reinstate all suspended wrestlers of the entity.
     *
     * @return callable Strategy function for wrestler reinstatement cascade
     */
    public static function wrestlers(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on reinstatement transitions
            if ($transition !== 'reinstate') {
                return;
            }

            // Check if entity has wrestler relationships (tag teams)
            if (! method_exists($entity, 'currentWrestlers')) {
                return;
            }

            // Get suspended wrestlers who need reinstatement
            $suspendedWrestlers = $entity->currentWrestlers()
                ->get()
                ->filter(fn (Wrestler $wrestler) => $wrestler->isSuspended());

            // Reinstate each suspended wrestler
            foreach ($suspendedWrestlers as $wrestler) {
                StatusTransitionPipeline::reinstate($wrestler, $date)->execute();
            }
        };
    }

    /**
     * Strategy to reinstate all suspended managers of the entity.
     *
     * @return callable Strategy function for manager reinstatement cascade
     */
    public static function managers(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on reinstatement transitions
            if ($transition !== 'reinstate') {
                return;
            }

            // Check if entity has manager relationships
            if (! method_exists($entity, 'currentManagers')) {
                return;
            }

            // Get suspended managers who need reinstatement
            $suspendedManagers = $entity->currentManagers()
                ->get()
                ->filter(fn (Manager $manager) => $manager->isSuspended());

            // Reinstate each suspended manager
            foreach ($suspendedManagers as $manager) {
                StatusTransitionPipeline::reinstate($manager, $date)->execute();
            }
        };
    }

    /**
     * Combined strategy to reinstate all suspended members (wrestlers and managers).
     *
     * @return callable Strategy function for complete member reinstatement cascade
     */
    public static function allMembers(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on reinstatement transitions
            if ($transition !== 'reinstate') {
                return;
            }

            // Execute individual strategies
            static::wrestlers()($entity, $date, $transition);
            static::managers()($entity, $date, $transition);
        };
    }
}
