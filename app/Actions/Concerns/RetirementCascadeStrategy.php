<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use App\Models\Managers\Manager;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Retirement cascade strategies for automatic relationship-based retirement.
 *
 * This class provides strategies for automatically retiring related entities when
 * a primary entity is retired. Common cascading scenarios include retiring wrestlers
 * and managers when a tag team is retired.
 *
 * BUSINESS CONTEXT:
 * When a tag team retires, there are different business scenarios:
 * - Sometimes the entire unit retires together (career-ending retirement)
 * - Sometimes only the partnership ends while members continue independently
 * - Managers may retire with their teams or continue managing other talent
 *
 * DESIGN PATTERN:
 * Strategy pattern - each method returns a callable strategy that can be used
 * with StatusTransitionPipeline.withCascade().
 *
 * @example
 * ```php
 * // Retire a tag team and automatically retire available members
 * StatusTransitionPipeline::retire($tagTeam, $date)
 *     ->withCascade(RetirementCascadeStrategy::wrestlers())
 *     ->withCascade(RetirementCascadeStrategy::managers())
 *     ->execute();
 *
 * // Retire only the partnership, not the individual members
 * StatusTransitionPipeline::retire($tagTeam, $date)->execute();
 * ```
 */
class RetirementCascadeStrategy
{
    /**
     * Strategy to retire all eligible wrestlers of the entity.
     *
     * Only retires wrestlers who can be retired according to business rules.
     * Wrestlers who cannot be retired (e.g., under contract, active storylines)
     * will continue their careers independently.
     *
     * @return callable Strategy function for wrestler retirement cascade
     */
    public static function wrestlers(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on retirement transitions
            if ($transition !== 'retire') {
                return;
            }

            // Check if entity has wrestler relationships (tag teams)
            if (! method_exists($entity, 'currentWrestlers')) {
                return;
            }

            // Get wrestlers who can be retired
            $eligibleWrestlers = $entity->currentWrestlers()
                ->get()
                ->filter(fn (Wrestler $wrestler) => $wrestler->canBeRetired());

            // Retire each eligible wrestler
            foreach ($eligibleWrestlers as $wrestler) {
                StatusTransitionPipeline::retire($wrestler, $date)->execute();
            }
        };
    }

    /**
     * Strategy to retire all eligible managers of the entity.
     *
     * Only retires managers who can be retired according to business rules.
     * Managers who cannot be retired will continue managing other talent.
     *
     * @return callable Strategy function for manager retirement cascade
     */
    public static function managers(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on retirement transitions
            if ($transition !== 'retire') {
                return;
            }

            // Check if entity has manager relationships
            if (! method_exists($entity, 'currentManagers')) {
                return;
            }

            // Get managers who can be retired
            $eligibleManagers = $entity->currentManagers()
                ->get()
                ->filter(fn (Manager $manager) => $manager->canBeRetired());

            // Retire each eligible manager
            foreach ($eligibleManagers as $manager) {
                StatusTransitionPipeline::retire($manager, $date)->execute();
            }
        };
    }

    /**
     * Combined strategy to retire all eligible members (wrestlers and managers).
     *
     * This represents a complete unit retirement where the entire team,
     * including all eligible members, retires together.
     *
     * @return callable Strategy function for complete member retirement cascade
     */
    public static function allMembers(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on retirement transitions
            if ($transition !== 'retire') {
                return;
            }

            // Execute individual strategies
            static::wrestlers()($entity, $date, $transition);
            static::managers()($entity, $date, $transition);
        };
    }

    /**
     * Conditional strategy to retire members only when explicitly requested.
     *
     * This allows for dynamic cascade behavior based on business logic
     * or user preferences (e.g., retirePartners parameter).
     *
     * @param  bool  $shouldRetireMembers  Whether to actually retire the members
     * @return callable Strategy function for conditional member retirement
     */
    public static function conditionalMembers(bool $shouldRetireMembers): callable
    {
        return function (Model $entity, Carbon $date, string $transition) use ($shouldRetireMembers): void {
            // Only cascade if explicitly requested and on retirement transitions
            if (! $shouldRetireMembers || $transition !== 'retire') {
                return;
            }

            // Execute the complete member retirement strategy
            static::allMembers()($entity, $date, $transition);
        };
    }
}
