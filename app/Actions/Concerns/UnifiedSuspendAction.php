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
 * Unified suspension action that can handle any suspendable entity.
 *
 * This action replaces the duplicate SuspendAction classes across different entity types
 * by using the StatusTransitionPipeline with entity-specific cascade strategies for
 * managing suspension workflows and member suspensions.
 *
 * BUSINESS CONTEXT:
 * Suspension temporarily removes entities from active competition while maintaining
 * employment status. Different entities have different cascading requirements:
 * - Wrestlers: May cascade to their managers
 * - TagTeams: May cascade to wrestlers and managers
 * - Managers: No cascading needed
 * - Referees: No cascading needed
 *
 * DESIGN PATTERN:
 * Strategy pattern - Uses cascade strategies to handle entity-specific suspension logic.
 * Template method - Provides consistent suspension workflow with customizable cascading.
 *
 * @example
 * ```php
 * // Suspend a wrestler (may cascade to managers)
 * UnifiedSuspendAction::run($wrestler, $date);
 *
 * // Suspend a tag team (cascades to wrestlers and managers)
 * UnifiedSuspendAction::run($tagTeam, $date);
 *
 * // Suspend a manager (no cascading)
 * UnifiedSuspendAction::run($manager, $date);
 * ```
 */
class UnifiedSuspendAction
{
    use AsAction;

    /**
     * Suspend an entity with appropriate cascading behavior.
     *
     * This method automatically determines the correct cascading strategy based on
     * the entity type and handles suspension with consistent validation and
     * member management.
     *
     * @param  Model  $entity  The entity to suspend (Wrestler, Manager, Referee, TagTeam)
     * @param  Carbon|null  $suspensionDate  The suspension date (defaults to now)
     * @param  string|null  $notes  Optional notes for the suspension record
     *
     * @throws Exception When entity cannot be suspended due to business rules
     *
     * @example
     * ```php
     * // Basic suspension
     * UnifiedSuspendAction::run($wrestler);
     *
     * // Suspension with specific date and notes
     * UnifiedSuspendAction::run($wrestler, Carbon::parse('2024-01-01'), 'Conduct violation');
     * ```
     */
    public function handle(Model $entity, ?Carbon $suspensionDate = null, ?string $notes = null): void
    {
        $pipeline = StatusTransitionPipeline::suspend($entity, $suspensionDate);

        // Add notes if provided
        if ($notes) {
            $pipeline = $pipeline->withNotes($notes);
        }

        // Add cascade strategies based on entity type and relationships
        $pipeline = $this->addSuspensionCascades($pipeline, $entity);

        // Execute the suspension with cascading
        $pipeline->execute();
    }

    /**
     * Add appropriate suspension cascade strategies based on entity type.
     *
     * @param  StatusTransitionPipeline  $pipeline  The pipeline to add strategies to
     * @param  Model  $entity  The entity being suspended
     * @return StatusTransitionPipeline Pipeline with cascade strategies added
     */
    protected function addSuspensionCascades(StatusTransitionPipeline $pipeline, Model $entity): StatusTransitionPipeline
    {
        // Use type-safe enum-based strategy selection
        $rosterType = RosterMemberType::fromModel($entity);

        return match ($rosterType) {
            RosterMemberType::WRESTLER => $this->addWrestlerSuspensionCascades($pipeline, $entity),
            RosterMemberType::TAG_TEAM => $this->addTagTeamSuspensionCascades($pipeline, $entity),
            RosterMemberType::MANAGER, RosterMemberType::REFEREE => $pipeline, // No cascading for individual people
            default => $pipeline // No special cascading needed
        };
    }

    /**
     * Add wrestler-specific suspension cascades.
     */
    protected function addWrestlerSuspensionCascades(StatusTransitionPipeline $pipeline, Model $wrestler): StatusTransitionPipeline
    {
        return $pipeline->withCascade(function (Model $entity, Carbon $date, string $transition) {
            if ($transition !== 'suspend') {
                return;
            }

            // Suspend current managers who are employed and not already suspended
            if ($entity instanceof Manageable) {
                $managersToSuspend = $entity->currentManagers()
                    ->get()
                    ->filter(fn ($manager) => $manager->isEmployed() && ! $manager->isSuspended());

                foreach ($managersToSuspend as $manager) {
                    StatusTransitionPipeline::suspend($manager, $date)->execute();
                }
            }
        });
    }

    /**
     * Add tag team-specific suspension cascades.
     */
    protected function addTagTeamSuspensionCascades(StatusTransitionPipeline $pipeline, Model $tagTeam): StatusTransitionPipeline
    {
        return $pipeline->withCascade(function (Model $entity, Carbon $date, string $transition) {
            if ($transition !== 'suspend') {
                return;
            }

            // Suspend current wrestlers who are employed and not already suspended
            if ($entity instanceof HasTagTeamWrestlers) {
                $wrestlersToSuspend = $entity->currentWrestlers()
                    ->get()
                    ->filter(fn ($wrestler) => $wrestler->isEmployed() && ! $wrestler->isSuspended());

                foreach ($wrestlersToSuspend as $wrestler) {
                    StatusTransitionPipeline::suspend($wrestler, $date)->execute();
                }
            }

            // Suspend current managers who are employed and not already suspended
            if ($entity instanceof Manageable) {
                $managersToSuspend = $entity->currentManagers()
                    ->get()
                    ->filter(fn ($manager) => $manager->isEmployed() && ! $manager->isSuspended());

                foreach ($managersToSuspend as $manager) {
                    StatusTransitionPipeline::suspend($manager, $date)->execute();
                }
            }
        });
    }

    /**
     * Suspend multiple entities with batch processing.
     *
     * @param  iterable<Model>  $entities  Collection of entities to suspend
     * @param  Carbon|null  $suspensionDate  Suspension date for all entities
     * @param  string|null  $notes  Optional notes for all suspension records
     */
    public static function batch(iterable $entities, ?Carbon $suspensionDate = null, ?string $notes = null): void
    {
        foreach ($entities as $entity) {
            static::run($entity, $suspensionDate, $notes);
        }
    }

    /**
     * Suspend entity without cascading (direct suspension only).
     *
     * @param  Model  $entity  The entity to suspend directly
     * @param  Carbon|null  $suspensionDate  The suspension date
     * @param  string|null  $notes  Optional notes
     */
    public static function direct(Model $entity, ?Carbon $suspensionDate = null, ?string $notes = null): void
    {
        $pipeline = StatusTransitionPipeline::suspend($entity, $suspensionDate);

        if ($notes) {
            $pipeline = $pipeline->withNotes($notes);
        }

        $pipeline->execute();
    }

    /**
     * Suspend entity with custom cascade strategies.
     *
     * @param  Model  $entity  The entity to suspend
     * @param  array<int, callable>  $cascadeStrategies  Array of cascade strategy callables
     * @param  Carbon|null  $suspensionDate  The suspension date
     * @param  string|null  $notes  Optional notes
     */
    public static function withCustomCascade(
        Model $entity,
        array $cascadeStrategies,
        ?Carbon $suspensionDate = null,
        ?string $notes = null
    ): void {
        $pipeline = StatusTransitionPipeline::suspend($entity, $suspensionDate);

        if ($notes) {
            $pipeline = $pipeline->withNotes($notes);
        }

        foreach ($cascadeStrategies as $strategy) {
            $pipeline = $pipeline->withCascade($strategy);
        }

        $pipeline->execute();
    }

    /**
     * Suspend members by type and availability criteria.
     *
     * @param  Model  $entity  The entity whose members to suspend
     * @param  array<int, string>  $memberTypes  Types to suspend ('wrestlers', 'managers', 'tag_teams')
     * @param  Carbon|null  $suspensionDate  The suspension date
     * @param  string|null  $notes  Optional notes
     */
    public static function suspendMembersByType(
        Model $entity,
        array $memberTypes = ['wrestlers', 'managers', 'tag_teams'],
        ?Carbon $suspensionDate = null,
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
                    ->filter(fn ($member) => $member->isEmployed() && ! $member->isSuspended());

                foreach ($members as $member) {
                    static::run($member, $suspensionDate, $notes);
                }
            }
        }
    }

    /**
     * Suspend available members (employed, not already suspended).
     *
     * @param  Model  $entity  The entity whose available members to suspend
     * @param  Carbon|null  $suspensionDate  The suspension date
     * @param  string|null  $notes  Optional notes
     */
    public static function suspendAvailableMembers(Model $entity, ?Carbon $suspensionDate = null, ?string $notes = null): void
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
                $allMembers = $allMembers->merge($entity->{$relation}()->get()); // @phpstan-ignore-line method.notFound
            }
        }

        if ($allMembers->isNotEmpty()) {
            // Filter to available members and suspend them
            MemberCollectionManager::from($allMembers)
                ->filterByEmploymentStatus('employed')
                ->filterBySuspensionStatus('active')
                ->batchSuspend($suspensionDate, $notes);
        }
    }
}
