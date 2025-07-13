<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Unified injury action that can handle any injurable entity.
 *
 * This action replaces the duplicate InjureAction classes across different entity types
 * by using the StatusTransitionPipeline with entity-specific cascade strategies for
 * managing injury workflows.
 *
 * BUSINESS CONTEXT:
 * Injury temporarily removes individual people (wrestlers, managers, referees) from
 * active participation while maintaining employment status. Only individual people
 * can be injured - tag teams, stables, and titles cannot be injured as business entities.
 *
 * INJURY CAPABILITY RULES:
 * - Wrestlers: Can be injured (individual person)
 * - Managers: Can be injured (individual person)
 * - Referees: Can be injured (individual person)
 * - TagTeams: Cannot be injured (business entity)
 * - Stables: Cannot be injured (business entity)
 * - Titles: Cannot be injured (business entity)
 *
 * DESIGN PATTERN:
 * Strategy pattern - Uses cascade strategies to handle entity-specific injury logic.
 * Template method - Provides consistent injury workflow with validation.
 *
 * @example
 * ```php
 * // Injure a wrestler
 * UnifiedInjureAction::run($wrestler, $date);
 *
 * // Injure a manager
 * UnifiedInjureAction::run($manager, $date);
 *
 * // Injure a referee
 * UnifiedInjureAction::run($referee, $date);
 * ```
 */
class UnifiedInjureAction
{
    use AsAction;

    /**
     * Injure an entity with appropriate validation and effects.
     *
     * This method validates that only individual people can be injured and handles
     * the injury workflow with consistent validation and potential cascading effects
     * on managed entities or teams.
     *
     * @param  Model  $entity  The entity to injure (Wrestler, Manager, Referee only)
     * @param  Carbon|null  $injuryDate  The injury date (defaults to now)
     * @param  string|null  $notes  Optional notes for the injury record (injury description)
     *
     * @throws Exception When entity cannot be injured due to business rules or entity type
     *
     * @example
     * ```php
     * // Basic injury
     * UnifiedInjureAction::run($wrestler);
     *
     * // Injury with specific date and description
     * UnifiedInjureAction::run($wrestler, Carbon::parse('2024-01-15'), 'Torn ACL during match');
     * ```
     */
    public function handle(Model $entity, ?Carbon $injuryDate = null, ?string $notes = null): void
    {
        // Validate entity can be injured
        $this->validateInjurable($entity);

        $pipeline = StatusTransitionPipeline::injure($entity, $injuryDate);

        // Add notes if provided (injury description)
        if ($notes) {
            $pipeline = $pipeline->withNotes($notes);
        }

        // Add cascade strategies based on entity type and relationships
        $pipeline = $this->addInjuryCascades($pipeline, $entity);

        // Execute the injury with cascading
        $pipeline->execute();
    }

    /**
     * Validate that the entity can be injured.
     *
     * @param  Model  $entity  The entity to validate
     *
     * @throws InvalidArgumentException When entity type cannot be injured
     */
    protected function validateInjurable(Model $entity): void
    {
        $entityType = class_basename($entity);

        // Only individual people can be injured
        $injurableTypes = ['Wrestler', 'Manager', 'Referee'];

        if (! in_array($entityType, $injurableTypes)) {
            throw new InvalidArgumentException(
                "Entity type '{$entityType}' cannot be injured. Only individual people (Wrestler, Manager, Referee) can be injured."
            );
        }
    }

    /**
     * Add appropriate injury cascade strategies based on entity type.
     *
     * @param  StatusTransitionPipeline  $pipeline  The pipeline to add strategies to
     * @param  Model  $entity  The entity being injured
     * @return StatusTransitionPipeline Pipeline with cascade strategies added
     */
    protected function addInjuryCascades(StatusTransitionPipeline $pipeline, Model $entity): StatusTransitionPipeline
    {
        // Get entity type for strategy selection
        $entityType = class_basename($entity);

        return match ($entityType) {
            'Wrestler' => $this->addWrestlerInjuryCascades($pipeline, $entity),
            'Manager' => $this->addManagerInjuryCascades($pipeline, $entity),
            'Referee' => $pipeline, // No cascading for referees
            default => $pipeline
        };
    }

    /**
     * Add wrestler-specific injury cascades.
     */
    protected function addWrestlerInjuryCascades(StatusTransitionPipeline $pipeline, Model $wrestler): StatusTransitionPipeline
    {
        return $pipeline->withCascade(function (Model $entity, Carbon $date, string $transition) {
            if ($transition !== 'injure') {
                return;
            }

            // Wrestler injury may affect tag team bookability, but no direct cascading
            // The tag team's isBookable() method will automatically handle this

            // No direct injury cascading to managers - managers remain available
            // to manage other wrestlers/teams while this wrestler recovers
        });
    }

    /**
     * Add manager-specific injury cascades.
     */
    protected function addManagerInjuryCascades(StatusTransitionPipeline $pipeline, Model $manager): StatusTransitionPipeline
    {
        return $pipeline->withCascade(function (Model $entity, Carbon $date, string $transition) {
            if ($transition !== 'injure') {
                return;
            }

            // Manager injury doesn't cascade to managed entities
            // Wrestlers and tag teams can continue without management temporarily
            // or may be assigned backup managers through separate actions
        });
    }

    /**
     * Injure multiple entities with batch processing.
     *
     * @param  iterable<Model>  $entities  Collection of entities to injure (must all be injurable types)
     * @param  Carbon|null  $injuryDate  Injury date for all entities
     * @param  string|null  $notes  Optional notes for all injury records
     */
    public static function batch(iterable $entities, ?Carbon $injuryDate = null, ?string $notes = null): void
    {
        foreach ($entities as $entity) {
            static::run($entity, $injuryDate, $notes);
        }
    }

    /**
     * Injure entity without cascading (direct injury only).
     *
     * @param  Model  $entity  The entity to injure directly
     * @param  Carbon|null  $injuryDate  The injury date
     * @param  string|null  $notes  Optional notes
     */
    public static function direct(Model $entity, ?Carbon $injuryDate = null, ?string $notes = null): void
    {
        $action = app(static::class);
        $action->validateInjurable($entity);

        $pipeline = StatusTransitionPipeline::injure($entity, $injuryDate);

        if ($notes) {
            $pipeline = $pipeline->withNotes($notes);
        }

        $pipeline->execute();
    }

    /**
     * Injure entity with custom cascade strategies.
     *
     * @param  Model  $entity  The entity to injure
     * @param  array<int, callable>  $cascadeStrategies  Array of cascade strategy callables
     * @param  Carbon|null  $injuryDate  The injury date
     * @param  string|null  $notes  Optional notes
     */
    public static function withCustomCascade(
        Model $entity,
        array $cascadeStrategies,
        ?Carbon $injuryDate = null,
        ?string $notes = null
    ): void {
        $action = app(static::class);
        $action->validateInjurable($entity);

        $pipeline = StatusTransitionPipeline::injure($entity, $injuryDate);

        if ($notes) {
            $pipeline = $pipeline->withNotes($notes);
        }

        foreach ($cascadeStrategies as $strategy) {
            $pipeline = $pipeline->withCascade($strategy);
        }

        $pipeline->execute();
    }

    /**
     * Get entities that can be injured from a collection.
     *
     * @param  iterable<Model>  $entities  Collection of entities to filter
     * @return array<int, Model> Only the entities that can be injured
     */
    public static function filterInjurable(iterable $entities): array
    {
        $injurableTypes = ['Wrestler', 'Manager', 'Referee'];
        $injurable = [];

        foreach ($entities as $entity) {
            $entityType = class_basename($entity);
            if (in_array($entityType, $injurableTypes)) {
                $injurable[] = $entity;
            }
        }

        return $injurable;
    }

    /**
     * Check if an entity can be injured.
     *
     * @param  Model  $entity  The entity to check
     * @return bool True if entity can be injured
     */
    public static function canBeInjured(Model $entity): bool
    {
        $entityType = class_basename($entity);

        return in_array($entityType, ['Wrestler', 'Manager', 'Referee']);
    }

    /**
     * Heal (end injury for) an entity.
     *
     * This is a convenience method that uses the existing StatusTransitionPipeline
     * to end an injury, effectively "healing" the entity.
     *
     * @param  Model  $entity  The entity to heal
     * @param  Carbon|null  $healingDate  The healing/recovery date
     * @param  string|null  $notes  Optional notes for the healing record
     */
    public static function heal(Model $entity, ?Carbon $healingDate = null, ?string $notes = null): void
    {
        // Validate entity can be injured (and therefore healed)
        $action = app(static::class);
        $action->validateInjurable($entity);

        // Use StatusTransitionPipeline to end the injury
        $pipeline = StatusTransitionPipeline::reinstate($entity, $healingDate);

        if ($notes) {
            $pipeline = $pipeline->withNotes($notes);
        }

        $pipeline->execute();
    }
}
