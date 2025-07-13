<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Unified employment action that can handle any employable entity.
 *
 * This action replaces the duplicate EmployAction classes across different entity types
 * by using the StatusTransitionPipeline with appropriate cascade strategies.
 *
 * BUSINESS CONTEXT:
 * Employment is a common operation across wrestlers, managers, referees, and tag teams.
 * Each entity type may have different cascading requirements (e.g., employing managers
 * when wrestlers are employed).
 *
 * DESIGN PATTERN:
 * Strategy pattern - Uses cascade strategies to handle entity-specific employment logic.
 * Template method - Provides consistent employment workflow with customizable cascading.
 *
 * @example
 * ```php
 * // Employ a wrestler with manager cascade
 * UnifiedEmployAction::run($wrestler, $date);
 *
 * // Employ a tag team with wrestler and manager cascade
 * UnifiedEmployAction::run($tagTeam, $date);
 *
 * // Employ a manager (no cascading needed)
 * UnifiedEmployAction::run($manager, $date);
 * ```
 */
class UnifiedEmployAction
{
    use AsAction;

    /**
     * Employ an entity with appropriate cascading behavior.
     *
     * This method automatically determines the correct cascading strategy based on
     * the entity type and relationships.
     *
     * @param  Model  $entity  The entity to employ (Wrestler, Manager, Referee, TagTeam)
     * @param  Carbon|null  $employmentDate  The employment date (defaults to now)
     * @param  string|null  $notes  Optional notes for the employment record
     *
     * @throws Exception When entity cannot be employed due to business rules
     *
     * @example
     * ```php
     * // Basic employment
     * UnifiedEmployAction::run($wrestler);
     *
     * // Employment with specific date and notes
     * UnifiedEmployAction::run($wrestler, Carbon::parse('2024-01-01'), 'New signing');
     * ```
     */
    public function handle(Model $entity, ?Carbon $employmentDate = null, ?string $notes = null): void
    {
        $pipeline = StatusTransitionPipeline::employ($entity, $employmentDate);

        // Add notes if provided
        if ($notes) {
            $pipeline = $pipeline->withNotes($notes);
        }

        // Add cascade strategies based on entity type and relationships
        $pipeline = $this->addCascadeStrategies($pipeline, $entity);

        // Execute the employment with cascading
        $pipeline->execute();
    }

    /**
     * Add appropriate cascade strategies based on entity type and relationships.
     *
     * @param  StatusTransitionPipeline  $pipeline  The pipeline to add strategies to
     * @param  Model  $entity  The entity being employed
     * @return StatusTransitionPipeline Pipeline with cascade strategies added
     */
    protected function addCascadeStrategies(StatusTransitionPipeline $pipeline, Model $entity): StatusTransitionPipeline
    {
        // Determine cascade strategies based on entity relationships
        if (method_exists($entity, 'currentManagers')) {
            $pipeline = $pipeline->withCascade(EmploymentCascadeStrategy::managers());
        }

        if (method_exists($entity, 'currentWrestlers')) {
            $pipeline = $pipeline->withCascade(EmploymentCascadeStrategy::wrestlers());
        }

        if (method_exists($entity, 'currentTagTeams')) {
            $pipeline = $pipeline->withCascade(EmploymentCascadeStrategy::tagTeams());
        }

        return $pipeline;
    }

    /**
     * Employ multiple entities with batch processing.
     *
     * @param  iterable<Model>  $entities  Collection of entities to employ
     * @param  Carbon|null  $employmentDate  Employment date for all entities
     * @param  string|null  $notes  Optional notes for all employment records
     */
    public static function batch(iterable $entities, ?Carbon $employmentDate = null, ?string $notes = null): void
    {
        foreach ($entities as $entity) {
            static::run($entity, $employmentDate, $notes);
        }
    }

    /**
     * Employ entity without cascading (direct employment only).
     *
     * @param  Model  $entity  The entity to employ directly
     * @param  Carbon|null  $employmentDate  The employment date
     * @param  string|null  $notes  Optional notes
     */
    public static function direct(Model $entity, ?Carbon $employmentDate = null, ?string $notes = null): void
    {
        $pipeline = StatusTransitionPipeline::employ($entity, $employmentDate);

        if ($notes) {
            $pipeline = $pipeline->withNotes($notes);
        }

        $pipeline->execute();
    }

    /**
     * Employ entity with custom cascade strategies.
     *
     * @param  Model  $entity  The entity to employ
     * @param  array<int, callable>  $cascadeStrategies  Array of cascade strategy callables
     * @param  Carbon|null  $employmentDate  The employment date
     * @param  string|null  $notes  Optional notes
     */
    public static function withCustomCascade(
        Model $entity,
        array $cascadeStrategies,
        ?Carbon $employmentDate = null,
        ?string $notes = null
    ): void {
        $pipeline = StatusTransitionPipeline::employ($entity, $employmentDate);

        if ($notes) {
            $pipeline = $pipeline->withNotes($notes);
        }

        foreach ($cascadeStrategies as $strategy) {
            $pipeline = $pipeline->withCascade($strategy);
        }

        $pipeline->execute();
    }
}
