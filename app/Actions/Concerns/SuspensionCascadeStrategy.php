<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use App\Models\Managers\Manager;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Suspension cascade strategies for automatic relationship-based suspension.
 *
 * This class provides strategies for automatically suspending related entities when
 * a primary entity is suspended. Common cascading scenarios include suspending
 * wrestlers and managers when a tag team is suspended.
 *
 * BUSINESS CONTEXT:
 * When a tag team is suspended, their wrestlers and managers should also be
 * suspended to maintain team suspension integrity. Only employed, non-suspended
 * members are affected to avoid duplicate suspensions.
 *
 * DESIGN PATTERN:
 * Strategy pattern - each method returns a callable strategy that can be used
 * with StatusTransitionPipeline.withCascade().
 *
 * @example
 * ```php
 * // Suspend a tag team and automatically suspend eligible members
 * StatusTransitionPipeline::suspend($tagTeam, $date)
 *     ->withCascade(SuspensionCascadeStrategy::wrestlers())
 *     ->withCascade(SuspensionCascadeStrategy::managers())
 *     ->execute();
 * ```
 */
class SuspensionCascadeStrategy
{
    /**
     * Strategy to suspend all eligible wrestlers of the entity.
     *
     * Only suspends wrestlers who are employed and not already suspended
     * to maintain team suspension integrity.
     *
     * @return callable Strategy function for wrestler suspension cascade
     */
    public static function wrestlers(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on suspension transitions
            if ($transition !== 'suspend') {
                return;
            }

            // Check if entity has wrestler relationships (tag teams)
            if (! method_exists($entity, 'currentWrestlers')) {
                return;
            }

            // Get employed, non-suspended wrestlers who need suspension
            $eligibleWrestlers = $entity->currentWrestlers()
                ->get()
                ->filter(fn (Wrestler $wrestler) => $wrestler->isEmployed() && ! $wrestler->isSuspended());

            // Suspend each eligible wrestler
            foreach ($eligibleWrestlers as $wrestler) {
                StatusTransitionPipeline::suspend($wrestler, $date)->execute();
            }
        };
    }

    /**
     * Strategy to suspend all eligible managers of the entity.
     *
     * Only suspends managers who are employed and not already suspended
     * to maintain team suspension integrity.
     *
     * @return callable Strategy function for manager suspension cascade
     */
    public static function managers(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on suspension transitions
            if ($transition !== 'suspend') {
                return;
            }

            // Check if entity has manager relationships
            if (! method_exists($entity, 'currentManagers')) {
                return;
            }

            // Get employed, non-suspended managers who need suspension
            $eligibleManagers = $entity->currentManagers()
                ->get()
                ->filter(fn (Manager $manager) => $manager->isEmployed() && ! $manager->isSuspended());

            // Suspend each eligible manager
            foreach ($eligibleManagers as $manager) {
                StatusTransitionPipeline::suspend($manager, $date)->execute();
            }
        };
    }

    /**
     * Combined strategy to suspend all eligible members (wrestlers and managers).
     *
     * This represents a complete team suspension where all eligible members
     * are suspended to maintain team integrity.
     *
     * @return callable Strategy function for complete member suspension cascade
     */
    public static function allMembers(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on suspension transitions
            if ($transition !== 'suspend') {
                return;
            }

            // Execute individual strategies
            static::wrestlers()($entity, $date, $transition);
            static::managers()($entity, $date, $transition);
        };
    }
}
