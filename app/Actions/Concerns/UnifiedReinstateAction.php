<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use App\Enums\Shared\RosterMemberType;
use App\Models\Contracts\HasTagTeamWrestlers;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\ProvidesCurrentTagTeams;
use App\Models\Contracts\ProvidesCurrentWrestlers;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Unified reinstatement action that can handle any suspendable entity.
 *
 * This action replaces the duplicate ReinstateAction classes across different entity types
 * by using the StatusTransitionPipeline with entity-specific cascade strategies for
 * managing reinstatement workflows and member reinstatements.
 *
 * BUSINESS CONTEXT:
 * Reinstatement restores entities to active status after suspension, allowing them
 * to participate in competitions again. Different entities have different cascading
 * requirements similar to suspension but in reverse:
 * - Wrestlers: May cascade to their managers if they were suspended due to wrestler suspension
 * - TagTeams: May cascade to wrestlers and managers if they were suspended as a unit
 * - Managers: Usually no cascading needed
 * - Referees: Usually no cascading needed
 *
 * DESIGN PATTERN:
 * Strategy pattern - Uses cascade strategies to handle entity-specific reinstatement logic.
 * Template method - Provides consistent reinstatement workflow with customizable cascading.
 *
 * @example
 * ```php
 * // Reinstate a wrestler (may cascade to managers)
 * UnifiedReinstateAction::run($wrestler, $date);
 *
 * // Reinstate a tag team (may cascade to wrestlers and managers)
 * UnifiedReinstateAction::run($tagTeam, $date);
 *
 * // Reinstate a manager (no cascading)
 * UnifiedReinstateAction::run($manager, $date);
 * ```
 */
class UnifiedReinstateAction
{
    use AsAction;

    /**
     * Reinstate an entity with appropriate cascading behavior.
     *
     * This method automatically determines the correct cascading strategy based on
     * the entity type and handles reinstatement with consistent validation and
     * member management.
     *
     * @param  Model  $entity  The entity to reinstate (Wrestler, Manager, Referee, TagTeam)
     * @param  Carbon|null  $reinstatementDate  The reinstatement date (defaults to now)
     * @param  string|null  $notes  Optional notes for the reinstatement record
     *
     * @throws Exception When entity cannot be reinstated due to business rules
     *
     * @example
     * ```php
     * // Basic reinstatement
     * UnifiedReinstateAction::run($wrestler);
     *
     * // Reinstatement with specific date and notes
     * UnifiedReinstateAction::run($wrestler, Carbon::parse('2024-06-01'), 'Suspension period complete');
     * ```
     */
    public function handle(Model $entity, ?Carbon $reinstatementDate = null, ?string $notes = null): void
    {
        $pipeline = StatusTransitionPipeline::reinstate($entity, $reinstatementDate);

        // Add notes if provided
        if ($notes) {
            $pipeline = $pipeline->withNotes($notes);
        }

        // Add cascade strategies based on entity type and relationships
        $pipeline = $this->addReinstatementCascades($pipeline, $entity);

        // Execute the reinstatement with cascading
        $pipeline->execute();
    }

    /**
     * Add appropriate reinstatement cascade strategies based on entity type.
     *
     * @param  StatusTransitionPipeline  $pipeline  The pipeline to add strategies to
     * @param  Model  $entity  The entity being reinstated
     * @return StatusTransitionPipeline Pipeline with cascade strategies added
     */
    protected function addReinstatementCascades(StatusTransitionPipeline $pipeline, Model $entity): StatusTransitionPipeline
    {
        // Use type-safe enum-based strategy selection
        $rosterType = RosterMemberType::fromModel($entity);

        return match ($rosterType) {
            RosterMemberType::WRESTLER => $this->addWrestlerReinstatementCascades($pipeline, $entity),
            RosterMemberType::TAG_TEAM => $this->addTagTeamReinstatementCascades($pipeline, $entity),
            RosterMemberType::MANAGER, RosterMemberType::REFEREE => $pipeline, // No cascading for individual people
            default => $pipeline // No special cascading needed
        };
    }

    /**
     * Add wrestler-specific reinstatement cascades.
     */
    protected function addWrestlerReinstatementCascades(StatusTransitionPipeline $pipeline, Model $wrestler): StatusTransitionPipeline
    {
        return $pipeline->withCascade(function (Model $entity, Carbon $date, string $transition) {
            if ($transition !== 'reinstate') {
                return;
            }

            // Optionally reinstate current managers who are suspended
            // This is conservative - only reinstate if the manager was likely suspended due to this wrestler
            if ($entity instanceof Manageable) {
                $managersToReinstate = $entity->currentManagers()
                    ->get()
                    ->filter(fn (Model $manager) => $manager->isSuspended());

                foreach ($managersToReinstate as $manager) {
                    // Only reinstate if manager doesn't have other suspended wrestlers/teams
                    if ($this->shouldReinstateManagedEntity($manager, $entity)) {
                        StatusTransitionPipeline::reinstate($manager, $date)->execute();
                    }
                }
            }
        });
    }

    /**
     * Add tag team-specific reinstatement cascades.
     */
    protected function addTagTeamReinstatementCascades(StatusTransitionPipeline $pipeline, Model $tagTeam): StatusTransitionPipeline
    {
        return $pipeline->withCascade(function (Model $entity, Carbon $date, string $transition) {
            if ($transition !== 'reinstate') {
                return;
            }

            // Reinstate current wrestlers who are suspended
            if ($entity instanceof HasTagTeamWrestlers) {
                $wrestlersToReinstate = $entity->currentWrestlers()
                    ->get()
                    ->filter(fn (Model $wrestler) => $wrestler->isSuspended());

                foreach ($wrestlersToReinstate as $wrestler) {
                    StatusTransitionPipeline::reinstate($wrestler, $date)->execute();
                }
            }

            // Reinstate current managers who are suspended
            if ($entity instanceof Manageable) {
                $managersToReinstate = $entity->currentManagers()
                    ->get()
                    ->filter(fn (Model $manager) => $manager->isSuspended());

                foreach ($managersToReinstate as $manager) {
                    // Only reinstate if manager doesn't have other suspended entities
                    if ($this->shouldReinstateManagedEntity($manager, $entity)) {
                        StatusTransitionPipeline::reinstate($manager, $date)->execute();
                    }
                }
            }
        });
    }

    /**
     * Determine if a manager should be reinstated based on their other managed entities.
     *
     * @param  Model  $manager  The manager to check
     * @param  Model  $entityBeingReinstated  The entity being reinstated
     * @return bool True if manager should be reinstated
     */
    protected function shouldReinstateManagedEntity(Model $manager, Model $entityBeingReinstated): bool
    {
        // Check if manager has other suspended wrestlers
        if ($manager instanceof ProvidesCurrentWrestlers) {
            $otherSuspendedWrestlers = $manager->currentWrestlers()
                ->get()
                ->filter(fn (Model $wrestler) => $wrestler->getKey() !== $entityBeingReinstated->getKey())
                ->filter(fn (Model $wrestler) => $wrestler->isSuspended());

            if ($otherSuspendedWrestlers->isNotEmpty()) {
                return false;
            }
        }

        // Check if manager has other suspended tag teams
        if ($manager instanceof ProvidesCurrentTagTeams) {
            $otherSuspendedTagTeams = $manager->currentTagTeams()
                ->get()
                ->filter(fn (Model $tagTeam) => $tagTeam->getKey() !== $entityBeingReinstated->getKey())
                ->filter(fn (Model $tagTeam) => $tagTeam->isSuspended());

            if ($otherSuspendedTagTeams->isNotEmpty()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reinstate multiple entities with batch processing.
     *
     * @param  iterable<Model>  $entities  Collection of entities to reinstate
     * @param  Carbon|null  $reinstatementDate  Reinstatement date for all entities
     * @param  string|null  $notes  Optional notes for all reinstatement records
     */
    public static function batch(iterable $entities, ?Carbon $reinstatementDate = null, ?string $notes = null): void
    {
        foreach ($entities as $entity) {
            static::run($entity, $reinstatementDate, $notes);
        }
    }

    /**
     * Reinstate entity without cascading (direct reinstatement only).
     *
     * @param  Model  $entity  The entity to reinstate directly
     * @param  Carbon|null  $reinstatementDate  The reinstatement date
     * @param  string|null  $notes  Optional notes
     */
    public static function direct(Model $entity, ?Carbon $reinstatementDate = null, ?string $notes = null): void
    {
        $pipeline = StatusTransitionPipeline::reinstate($entity, $reinstatementDate);

        if ($notes) {
            $pipeline = $pipeline->withNotes($notes);
        }

        $pipeline->execute();
    }

    /**
     * Reinstate entity with custom cascade strategies.
     *
     * @param  Model  $entity  The entity to reinstate
     * @param  array<int, callable>  $cascadeStrategies  Array of cascade strategy callables
     * @param  Carbon|null  $reinstatementDate  The reinstatement date
     * @param  string|null  $notes  Optional notes
     */
    public static function withCustomCascade(
        Model $entity,
        array $cascadeStrategies,
        ?Carbon $reinstatementDate = null,
        ?string $notes = null
    ): void {
        $pipeline = StatusTransitionPipeline::reinstate($entity, $reinstatementDate);

        if ($notes) {
            $pipeline = $pipeline->withNotes($notes);
        }

        foreach ($cascadeStrategies as $strategy) {
            $pipeline = $pipeline->withCascade($strategy);
        }

        $pipeline->execute();
    }

    /**
     * Reinstate suspended members by type.
     *
     * @param  Model  $entity  The entity whose suspended members to reinstate
     * @param  array<int, string>  $memberTypes  Types to reinstate ('wrestlers', 'managers', 'tag_teams')
     * @param  Carbon|null  $reinstatementDate  The reinstatement date
     * @param  string|null  $notes  Optional notes
     */
    public static function reinstateMembersByType(
        Model $entity,
        array $memberTypes = ['wrestlers', 'managers', 'tag_teams'],
        ?Carbon $reinstatementDate = null,
        ?string $notes = null
    ): void {
        foreach ($memberTypes as $memberType) {
            $relationshipMethod = 'current'.ucfirst($memberType);

            // Use interface-based checking instead of method_exists
            $hasMethod = match ($memberType) {
                'wrestlers' => $entity instanceof ProvidesCurrentWrestlers,
                'managers' => $entity instanceof Manageable,
                'tag_teams' => $entity instanceof ProvidesCurrentTagTeams,
                default => false
            };

            if ($hasMethod) {
                $members = $entity->{$relationshipMethod}()
                    ->get()
                    ->filter(fn (Model $member) => $member instanceof \App\Models\Contracts\Suspendable && $member->isSuspended());

                foreach ($members as $member) {
                    static::run($member, $reinstatementDate, $notes);
                }
            }
        }
    }

    /**
     * Reinstate all suspended members.
     *
     * @param  Model  $entity  The entity whose suspended members to reinstate
     * @param  Carbon|null  $reinstatementDate  The reinstatement date
     * @param  string|null  $notes  Optional notes
     */
    public static function reinstateAllSuspendedMembers(Model $entity, ?Carbon $reinstatementDate = null, ?string $notes = null): void
    {
        // Use MemberCollectionManager for sophisticated filtering
        $allMembers = collect();

        // Collect all member types
        $memberRelations = ['currentWrestlers', 'currentManagers', 'currentTagTeams'];

        foreach ($memberRelations as $relation) {
            // Use interface-based checking instead of method_exists
            $hasRelation = match ($relation) {
                'currentWrestlers' => $entity instanceof ProvidesCurrentWrestlers,
                'currentManagers' => $entity instanceof Manageable,
                'currentTagTeams' => $entity instanceof ProvidesCurrentTagTeams, // @phpstan-ignore-line match.alwaysTrue
                default => false
            };

            if ($hasRelation) {
                // @phpstan-ignore-next-line method.notFound
                $allMembers = $allMembers->merge($entity->{$relation}()->get());
            }
        }

        if ($allMembers->isNotEmpty()) {
            // Filter to suspended members and reinstate them
            MemberCollectionManager::from($allMembers)
                ->filterBySuspensionStatus('suspended')
                ->batchReinstate($reinstatementDate, $notes);
        }
    }
}
