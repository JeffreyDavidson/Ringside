<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Employment cascade strategies for automatic relationship-based employment.
 *
 * This class provides strategies for automatically employing related entities when
 * a primary entity is employed. Common cascading scenarios include employing managers
 * when wrestlers or tag teams are employed.
 *
 * BUSINESS CONTEXT:
 * Wrestling promotions require managers to be employed in order to actively manage
 * talent. When a wrestler or tag team gets employed, their managers should also be
 * employed if they aren't already.
 *
 * DESIGN PATTERN:
 * Strategy pattern - each method returns a callable strategy that can be used
 * with StatusTransitionPipeline.withCascade().
 *
 * @example
 * ```php
 * // Employ a wrestler and automatically employ their managers
 * StatusTransitionPipeline::employ($wrestler, $date)
 *     ->withCascade(EmploymentCascadeStrategy::managers())
 *     ->execute();
 *
 * // Employ a tag team and cascade to wrestlers and managers
 * StatusTransitionPipeline::employ($tagTeam, $date)
 *     ->withCascade(EmploymentCascadeStrategy::wrestlers())
 *     ->withCascade(EmploymentCascadeStrategy::managers())
 *     ->execute();
 * ```
 */
class EmploymentCascadeStrategy
{
    /**
     * Strategy to employ all unemployed managers of the entity.
     *
     * @return callable Strategy function for manager employment cascade
     */
    public static function managers(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on employment transitions
            if ($transition !== 'employ') {
                return;
            }

            // Check if entity has manager relationships
            if (! method_exists($entity, 'currentManagers')) {
                return;
            }

            $unemployedManagers = $entity->currentManagers()
                ->get()
                ->filter(fn ($manager) => ! $manager->isEmployed());

            foreach ($unemployedManagers as $manager) {
                StatusTransitionPipeline::employ($manager, $date)->execute();
            }
        };
    }

    /**
     * Strategy to employ all unemployed wrestlers of the entity (for tag teams).
     *
     * @return callable Strategy function for wrestler employment cascade
     */
    public static function wrestlers(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on employment transitions
            if ($transition !== 'employ') {
                return;
            }

            // Check if entity has wrestler relationships (tag teams)
            if (! method_exists($entity, 'currentWrestlers')) {
                return;
            }

            $unemployedWrestlers = $entity->currentWrestlers()
                ->get()
                ->filter(fn ($wrestler) => ! $wrestler->isEmployed());

            foreach ($unemployedWrestlers as $wrestler) {
                StatusTransitionPipeline::employ($wrestler, $date)->execute();
            }
        };
    }

    /**
     * Strategy to employ all unemployed tag teams of the entity (for stables).
     *
     * @return callable Strategy function for tag team employment cascade
     */
    public static function tagTeams(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on employment transitions
            if ($transition !== 'employ') {
                return;
            }

            // Check if entity has tag team relationships (stables)
            if (! method_exists($entity, 'currentTagTeams')) {
                return;
            }

            $unemployedTagTeams = $entity->currentTagTeams()
                ->get()
                ->filter(fn ($tagTeam) => ! $tagTeam->isEmployed());

            foreach ($unemployedTagTeams as $tagTeam) {
                StatusTransitionPipeline::employ($tagTeam, $date)
                    ->withCascade(self::wrestlers())
                    ->withCascade(self::managers())
                    ->execute();
            }
        };
    }

    /**
     * Combined strategy to employ all unemployed members (wrestlers, tag teams, managers).
     * Useful for stable employment that needs to cascade to all member types.
     *
     * @return callable Strategy function for complete member employment cascade
     */
    public static function allMembers(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on employment transitions
            if ($transition !== 'employ') {
                return;
            }

            // Prevent infinite recursion by tracking executed entities
            static $executed = [];
            $entityKey = get_class($entity).':'.$entity->getKey();

            if (in_array($entityKey, $executed)) {
                return;
            }
            $executed[] = $entityKey;

            // Employ wrestlers first (they may have managers)
            if (method_exists($entity, 'currentWrestlers')) {
                $unemployedWrestlers = $entity->currentWrestlers()
                    ->get()
                    ->filter(fn ($wrestler) => ! $wrestler->isEmployed());

                foreach ($unemployedWrestlers as $wrestler) {
                    StatusTransitionPipeline::employ($wrestler, $date)
                        ->withCascade(self::managers())
                        ->execute();
                }
            }

            // Employ tag teams (they may have wrestlers and managers)
            if (method_exists($entity, 'currentTagTeams')) {
                $unemployedTagTeams = $entity->currentTagTeams()
                    ->get()
                    ->filter(fn ($tagTeam) => ! $tagTeam->isEmployed());

                foreach ($unemployedTagTeams as $tagTeam) {
                    StatusTransitionPipeline::employ($tagTeam, $date)
                        ->withCascade(self::wrestlers())
                        ->withCascade(self::managers())
                        ->execute();
                }
            }

            // Employ managers last (they don't cascade to anyone)
            if (method_exists($entity, 'currentManagers')) {
                $unemployedManagers = $entity->currentManagers()
                    ->get()
                    ->filter(fn ($manager) => ! $manager->isEmployed());

                foreach ($unemployedManagers as $manager) {
                    StatusTransitionPipeline::employ($manager, $date)->execute();
                }
            }

            // Clear the executed list for this cascade chain
            $executed = [];
        };
    }

    /**
     * Custom cascade strategy builder for specific employment patterns.
     *
     * @param  array<int, string>  $relationships  Array of relationship method names to cascade
     * @return callable Custom strategy function
     *
     * @example
     * ```php
     * // Custom cascade for specific relationships
     * $customCascade = EmploymentCascadeStrategy::custom(['currentManagers', 'currentPartners']);
     * StatusTransitionPipeline::employ($entity, $date)->withCascade($customCascade)->execute();
     * ```
     */
    public static function custom(array $relationships): callable
    {
        return function (Model $entity, Carbon $date, string $transition) use ($relationships): void {
            // Only cascade on employment transitions
            if ($transition !== 'employ') {
                return;
            }

            foreach ($relationships as $relationship) {
                if (! method_exists($entity, $relationship)) {
                    continue;
                }

                $unemployedEntities = $entity->{$relationship}
                    ->filter(fn ($relatedEntity) => ! $relatedEntity->isEmployed());

                foreach ($unemployedEntities as $relatedEntity) {
                    StatusTransitionPipeline::employ($relatedEntity, $date)->execute();
                }
            }
        };
    }
}
