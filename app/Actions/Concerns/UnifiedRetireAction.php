<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use App\Enums\Shared\RosterMemberType;
use App\Models\Contracts\CanBeATagTeamMember;
use App\Models\Contracts\CanBeChampion;
use App\Models\Contracts\HasStableMembership;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\ProvidesCurrentTagTeams;
use App\Models\Contracts\ProvidesCurrentWrestlers;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Unified retirement action that can handle any retirable entity.
 *
 * This action replaces the duplicate RetireAction classes across different entity types
 * by using the StatusTransitionPipeline with entity-specific cascade strategies for
 * complex retirement workflows.
 *
 * BUSINESS CONTEXT:
 * Retirement is a complex operation that varies by entity type. Wrestlers need tag team
 * and manager relationship management, while titles and stables have different ending
 * requirements. This unified approach handles all retirement complexities consistently.
 *
 * DESIGN PATTERN:
 * Strategy pattern - Uses cascade strategies to handle entity-specific retirement logic.
 * Template method - Provides consistent retirement workflow with customizable cascading.
 *
 * @example
 * ```php
 * // Retire a wrestler with complex cascading
 * UnifiedRetireAction::run($wrestler, $date);
 *
 * // Retire a title (simple retirement)
 * UnifiedRetireAction::run($title, $date);
 *
 * // Retire a stable with member handling
 * UnifiedRetireAction::run($stable, $date);
 * ```
 */
class UnifiedRetireAction
{
    use AsAction;

    /**
     * Retire an entity with appropriate cascading behavior.
     *
     * This method automatically determines the correct cascading strategy based on
     * the entity type and handles all retirement complexities including status ending,
     * relationship management, and cascade effects.
     *
     * @param  Model  $entity  The entity to retire (Wrestler, Manager, Referee, TagTeam, Title, Stable)
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     * @param  string|null  $notes  Optional notes for the retirement record
     *
     * @throws Exception When entity cannot be retired due to business rules
     *
     * @example
     * ```php
     * // Basic retirement
     * UnifiedRetireAction::run($wrestler);
     *
     * // Retirement with specific date and notes
     * UnifiedRetireAction::run($wrestler, Carbon::parse('2024-12-31'), 'Hall of Fame induction');
     * ```
     */
    public function handle(Model $entity, ?Carbon $retirementDate = null, ?string $notes = null): void
    {
        $pipeline = StatusTransitionPipeline::retire($entity, $retirementDate);

        // Add notes if provided
        if ($notes) {
            $pipeline = $pipeline->withNotes($notes);
        }

        // Add cascade strategies based on entity type and relationships
        $pipeline = $this->addRetirementCascades($pipeline, $entity);

        // Execute the retirement with cascading
        $pipeline->execute();
    }

    /**
     * Add appropriate retirement cascade strategies based on entity type.
     *
     * @param  StatusTransitionPipeline  $pipeline  The pipeline to add strategies to
     * @param  Model  $entity  The entity being retired
     * @return StatusTransitionPipeline Pipeline with cascade strategies added
     */
    protected function addRetirementCascades(StatusTransitionPipeline $pipeline, Model $entity): StatusTransitionPipeline
    {
        // Use type-safe enum-based strategy selection
        try {
            $rosterType = RosterMemberType::fromModel($entity);

            return match ($rosterType) {
                RosterMemberType::WRESTLER => $this->addWrestlerRetirementCascades($pipeline, $entity),
                RosterMemberType::TAG_TEAM => $this->addTagTeamRetirementCascades($pipeline, $entity),
                RosterMemberType::MANAGER, RosterMemberType::REFEREE => $this->addIndividualRetirementCascades($pipeline, $entity),
                default => $pipeline // No special cascading needed
            };
        } catch (InvalidArgumentException) {
            // Handle non-roster entities (Title, Stable, etc.) with class-based matching
            $entityType = class_basename($entity);

            return match ($entityType) {
                'Stable' => $this->addStableRetirementCascades($pipeline, $entity),
                'Title' => $this->addTitleRetirementCascades($pipeline, $entity),
                default => $pipeline // No special cascading needed
            };
        }
    }

    /**
     * Add wrestler-specific retirement cascades.
     */
    protected function addWrestlerRetirementCascades(StatusTransitionPipeline $pipeline, Model $wrestler): StatusTransitionPipeline
    {
        return $pipeline->withCascade(function (Model $entity, Carbon $date, string $transition) {
            if ($transition !== 'retire') {
                return;
            }

            // Handle tag team relationships
            if ($entity instanceof CanBeATagTeamMember && $entity->isAMemberOfCurrentTagTeam()) {
                // Remove entity from current tag team
                $currentTeam = $entity->currentTagTeam()->first();
                if ($currentTeam) {
                    $repository = app($this->getRepositoryClass($entity));
                    if (method_exists($repository, 'removeFromCurrentTagTeam')) {
                        $repository->removeFromCurrentTagTeam($entity, $date);
                    }
                }
            }

            // Handle manager relationships
            if ($entity instanceof Manageable) {
                if ($entity->currentManagers()->exists()) {
                    // End manager relationships
                    $repository = app($this->getRepositoryClass($entity));
                    if (method_exists($repository, 'removeCurrentManagers')) {
                        $repository->removeCurrentManagers($entity, $date);
                    }
                }
            }

            // Handle stable relationships
            if ($entity instanceof HasStableMembership && $entity->isInStable()) {
                // Remove entity from current stable
                $entity->leaveStable($date);
            }
        });
    }

    /**
     * Add tag team-specific retirement cascades.
     */
    protected function addTagTeamRetirementCascades(StatusTransitionPipeline $pipeline, Model $tagTeam): StatusTransitionPipeline
    {
        return $pipeline->withCascade(function (Model $entity, Carbon $date, string $transition) {
            if ($transition !== 'retire') {
                return;
            }

            // Handle stable relationships
            if ($entity instanceof HasStableMembership && $entity->isInStable()) {
                // Remove entity from current stable
                $entity->leaveStable($date);
            }

            // Handle manager relationships
            if ($entity instanceof Manageable && $entity->currentManagers()->exists()) {
                $repository = app($this->getRepositoryClass($entity));
                if (method_exists($repository, 'removeCurrentManagers')) {
                    $repository->removeCurrentManagers($entity, $date);
                }
            }
        });
    }

    /**
     * Add stable-specific retirement cascades.
     */
    protected function addStableRetirementCascades(StatusTransitionPipeline $pipeline, Model $stable): StatusTransitionPipeline
    {
        return $pipeline->withCascade(function (Model $entity, Carbon $date, string $transition) {
            if ($transition !== 'retire') {
                return;
            }

            // Remove all current members from the stable
            $repository = app($this->getRepositoryClass($entity));

            // Remove wrestlers
            if ($entity instanceof ProvidesCurrentWrestlers) {
                if ($entity->currentWrestlers()->exists()) {
                    foreach ($entity->currentWrestlers()->get() as $wrestler) {
                        if (method_exists($repository, 'removeWrestler')) {
                            $repository->removeWrestler($entity, $wrestler, $date);
                        }
                    }
                }
            }

            // Remove tag teams
            if ($entity instanceof ProvidesCurrentTagTeams) {
                if ($entity->currentTagTeams()->exists()) {
                    foreach ($entity->currentTagTeams()->get() as $tagTeam) {
                        if (method_exists($repository, 'removeTagTeam')) {
                            $repository->removeTagTeam($entity, $tagTeam, $date);
                        }
                    }
                }
            }

            // Remove managers
            if ($entity instanceof Manageable && $entity->currentManagers()->exists()) {
                foreach ($entity->currentManagers()->get() as $manager) {
                    if (method_exists($repository, 'removeManager')) {
                        $repository->removeManager($entity, $manager, $date);
                    }
                }
            }
        });
    }

    /**
     * Add individual person (Manager/Referee) retirement cascades.
     */
    protected function addIndividualRetirementCascades(StatusTransitionPipeline $pipeline, Model $individual): StatusTransitionPipeline
    {
        return $pipeline->withCascade(function (Model $entity, Carbon $date, string $transition) {
            if ($transition !== 'retire') {
                return;
            }

            // Handle stable relationships for managers
            if ($entity instanceof HasStableMembership && $entity->isInStable()) {
                // Remove entity from current stable
                $entity->leaveStable($date);
            }

            // Handle managed entities (for managers)
            if ($entity instanceof ProvidesCurrentWrestlers && $entity->currentWrestlers()->exists()) {
                $repository = app($this->getRepositoryClass($entity));
                if (method_exists($repository, 'removeCurrentWrestlers')) {
                    $repository->removeCurrentWrestlers($entity, $date);
                }
            }

            if ($entity instanceof ProvidesCurrentTagTeams && $entity->currentTagTeams()->exists()) {
                $repository = app($this->getRepositoryClass($entity));
                if (method_exists($repository, 'removeCurrentTagTeams')) {
                    $repository->removeCurrentTagTeams($entity, $date);
                }
            }
        });
    }

    /**
     * Add title-specific retirement cascades.
     */
    protected function addTitleRetirementCascades(StatusTransitionPipeline $pipeline, Model $title): StatusTransitionPipeline
    {
        return $pipeline->withCascade(function (Model $entity, Carbon $date, string $transition) {
            if ($transition !== 'retire') {
                return;
            }

            // End current championship if active
            if ($entity instanceof CanBeChampion && $entity->isChampion()) {
                $currentChampionship = $entity->currentChampionship()->first();
                if ($currentChampionship) {
                    // End the championship directly on the model
                    $currentChampionship->update(['lost_at' => $date]);
                }
            }
        });
    }

    /**
     * Get the repository class name for an entity.
     */
    protected function getRepositoryClass(Model $entity): string
    {
        $entityClass = get_class($entity);
        $baseName = class_basename($entityClass);

        // Handle the case where models are in subdirectories but repositories are not
        // App\Models\Wrestlers\Wrestler -> App\Repositories\WrestlerRepository
        $namespace = 'App\\Repositories\\';

        return $namespace.$baseName.'Repository';
    }

    /**
     * Retire multiple entities with batch processing.
     *
     * @param  iterable<Model>  $entities  Collection of entities to retire
     * @param  Carbon|null  $retirementDate  Retirement date for all entities
     * @param  string|null  $notes  Optional notes for all retirement records
     */
    public static function batch(iterable $entities, ?Carbon $retirementDate = null, ?string $notes = null): void
    {
        foreach ($entities as $entity) {
            static::run($entity, $retirementDate, $notes);
        }
    }

    /**
     * Retire entity without cascading (direct retirement only).
     *
     * @param  Model  $entity  The entity to retire directly
     * @param  Carbon|null  $retirementDate  The retirement date
     * @param  string|null  $notes  Optional notes
     */
    public static function direct(Model $entity, ?Carbon $retirementDate = null, ?string $notes = null): void
    {
        $pipeline = StatusTransitionPipeline::retire($entity, $retirementDate);

        if ($notes) {
            $pipeline = $pipeline->withNotes($notes);
        }

        $pipeline->execute();
    }

    /**
     * Retire entity with custom cascade strategies.
     *
     * @param  Model  $entity  The entity to retire
     * @param  array<int, callable>  $cascadeStrategies  Array of cascade strategy callables
     * @param  Carbon|null  $retirementDate  The retirement date
     * @param  string|null  $notes  Optional notes
     */
    public static function withCustomCascade(
        Model $entity,
        array $cascadeStrategies,
        ?Carbon $retirementDate = null,
        ?string $notes = null
    ): void {
        $pipeline = StatusTransitionPipeline::retire($entity, $retirementDate);

        if ($notes) {
            $pipeline = $pipeline->withNotes($notes);
        }

        foreach ($cascadeStrategies as $strategy) {
            $pipeline = $pipeline->withCascade($strategy);
        }

        $pipeline->execute();
    }
}
