<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use App\Data\Stables\StableData;
use App\Models\Managers\Manager;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\StableRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * High-level orchestrator for complex stable membership operations.
 *
 * This orchestrator handles sophisticated stable management workflows that involve
 * multiple entities, transfers, mergers, splits, and cascading status changes.
 * It builds on top of the MemberCollectionManager and StatusTransitionPipeline.
 *
 * BUSINESS CONTEXT:
 * Wrestling stables are complex entities with multiple member types (wrestlers,
 * tag teams, managers) that require coordinated management. Operations like
 * merging stables, splitting members, or transferring entire groups need
 * careful orchestration to maintain data integrity and business rules.
 *
 * DESIGN PATTERN:
 * Orchestrator pattern - Coordinates multiple lower-level operations.
 * Builder pattern - Chainable methods for building complex operations.
 * Command pattern - Encapsulates complex operations as executable commands.
 *
 * @example
 * ```php
 * // Merge two stables and employ all members
 * StableMembershipOrchestrator::mergeStables($stableA, $stableB, 'New Stable Name')
 *     ->withEmploymentCascade($date)
 *     ->execute();
 *
 * // Split stable and transfer specific members
 * StableMembershipOrchestrator::splitStable($originalStable, 'New Stable')
 *     ->transferWrestlers($wrestlerCollection)
 *     ->transferManagers($managerCollection)
 *     ->execute();
 * ```
 */
class StableMembershipOrchestrator
{
    protected StableRepository $stableRepository;

    protected ?Stable $sourceStable = null;

    protected ?Stable $targetStable = null;

    protected ?string $newStableName = null;

    /** @var array<int, array<string, mixed>> */
    protected array $operations = [];

    /** @var array<int, callable> */
    protected array $cascadeStrategies = [];

    protected ?Carbon $effectiveDate = null;

    /**
     * Create a new stable membership orchestrator.
     */
    public function __construct(StableRepository $stableRepository)
    {
        $this->stableRepository = $stableRepository;
    }

    /**
     * Create orchestrator for stable merger operation.
     *
     * @param  Stable  $primaryStable  The stable that will absorb members
     * @param  Stable  $secondaryStable  The stable whose members will be transferred
     * @param  string|null  $newName  Optional new name for the merged stable
     */
    public static function mergeStables(Stable $primaryStable, Stable $secondaryStable, ?string $newName = null): self
    {
        $orchestrator = new self(app(StableRepository::class));
        $orchestrator->sourceStable = $secondaryStable;
        $orchestrator->targetStable = $primaryStable;
        $orchestrator->newStableName = $newName;

        // Queue the merge operation
        $orchestrator->operations[] = ['type' => 'merge', 'primary' => $primaryStable, 'secondary' => $secondaryStable];

        return $orchestrator;
    }

    /**
     * Create orchestrator for stable split operation.
     *
     * @param  Stable  $originalStable  The stable to split
     * @param  string  $newStableName  Name for the new stable
     */
    public static function splitStable(Stable $originalStable, string $newStableName): self
    {
        $orchestrator = new self(app(StableRepository::class));
        $orchestrator->sourceStable = $originalStable;
        $orchestrator->newStableName = $newStableName;

        // Queue the split operation
        $orchestrator->operations[] = ['type' => 'split', 'original' => $originalStable, 'newName' => $newStableName];

        return $orchestrator;
    }

    /**
     * Create orchestrator for transferring members between stables.
     *
     * @param  Stable  $fromStable  Source stable
     * @param  Stable  $toStable  Destination stable
     */
    public static function transferMembers(Stable $fromStable, Stable $toStable): self
    {
        $orchestrator = new self(app(StableRepository::class));
        $orchestrator->sourceStable = $fromStable;
        $orchestrator->targetStable = $toStable;

        return $orchestrator;
    }

    /**
     * Set the effective date for all operations.
     */
    public function onDate(Carbon $date): self
    {
        $this->effectiveDate = $date;

        return $this;
    }

    /**
     * Transfer specific wrestlers to the target stable.
     *
     * @param  Collection<int, Model>|array<int, Model>|Model  $wrestlers  Wrestlers to transfer
     */
    public function transferWrestlers(Collection|array|Model $wrestlers): self
    {
        $wrestlerCollection = $this->normalizeToCollection($wrestlers);

        $this->operations[] = [
            'type' => 'transfer_wrestlers',
            'entities' => $wrestlerCollection,
        ];

        return $this;
    }

    /**
     * Transfer specific tag teams to the target stable.
     *
     * @param  Collection<int, Model>|array<int, Model>|Model  $tagTeams  Tag teams to transfer
     */
    public function transferTagTeams(Collection|array|Model $tagTeams): self
    {
        $tagTeamCollection = $this->normalizeToCollection($tagTeams);

        $this->operations[] = [
            'type' => 'transfer_tag_teams',
            'entities' => $tagTeamCollection,
        ];

        return $this;
    }

    /**
     * Transfer specific managers to the target stable.
     *
     * @param  Collection<int, Model>|array<int, Model>|Model  $managers  Managers to transfer
     */
    public function transferManagers(Collection|array|Model $managers): self
    {
        $managerCollection = $this->normalizeToCollection($managers);

        $this->operations[] = [
            'type' => 'transfer_managers',
            'entities' => $managerCollection,
        ];

        return $this;
    }

    /**
     * Transfer all available members (employed, not suspended, not injured).
     */
    public function transferAllAvailableMembers(): self
    {
        $this->operations[] = ['type' => 'transfer_all_available'];

        return $this;
    }

    /**
     * Transfer members by status criteria.
     *
     * @param  array<string, mixed>  $criteria  Filter criteria for MemberCollectionManager
     */
    public function transferMembersByCriteria(array $criteria): self
    {
        $this->operations[] = [
            'type' => 'transfer_by_criteria',
            'criteria' => $criteria,
        ];

        return $this;
    }

    /**
     * Add employment cascade after the main operations.
     *
     * This will employ all unemployed members in the target stable.
     */
    public function withEmploymentCascade(): self
    {
        $this->cascadeStrategies[] = 'employment';

        return $this;
    }

    /**
     * Add suspension cascade for specific member types.
     *
     * @param  array<int, string>  $memberTypes  Types to suspend ('wrestlers', 'managers', 'tag_teams')
     */
    public function withSuspensionCascade(array $memberTypes = ['wrestlers', 'managers', 'tag_teams']): self
    {
        $this->cascadeStrategies[] = ['type' => 'suspension', 'members' => $memberTypes];

        return $this;
    }

    /**
     * Add retirement cascade for the source stable after transfer.
     */
    public function withSourceStableRetirement(): self
    {
        $this->cascadeStrategies[] = 'retire_source';

        return $this;
    }

    /**
     * Execute all queued operations within a database transaction.
     *
     * @return Stable|array<int, Stable> The resulting stable(s) from the operations
     */
    public function execute(): Stable|array
    {
        return DB::transaction(function () {
            $results = [];
            $effectiveDate = $this->effectiveDate ?? now();

            // Execute main operations
            foreach ($this->operations as $operation) {
                $result = $this->executeOperation($operation, $effectiveDate);
                if ($result) {
                    $results[] = $result;
                }
            }

            // Execute cascade strategies
            foreach ($this->cascadeStrategies as $strategy) {
                $this->executeCascadeStrategy($strategy, $effectiveDate);
            }

            // Return the appropriate result
            if (count($results) === 1) {
                return $results[0];
            }

            return $results;
        });
    }

    /**
     * Execute a single operation.
     *
     * @param  array<string, mixed>  $operation
     */
    protected function executeOperation(array $operation, Carbon $date): ?Stable
    {
        return match ($operation['type']) {
            'merge' => $this->executeMerge($operation['primary'], $operation['secondary'], $date),
            'split' => $this->executeSplit($operation['original'], $operation['newName'], $date),
            'transfer_wrestlers' => $this->executeWrestlerTransfer($operation['entities'], $date),
            'transfer_tag_teams' => $this->executeTagTeamTransfer($operation['entities'], $date),
            'transfer_managers' => $this->executeManagerTransfer($operation['entities'], $date),
            'transfer_all_available' => $this->executeAvailableMemberTransfer($date),
            'transfer_by_criteria' => $this->executeCriteriaTransfer($operation['criteria'], $date),
            default => null
        };
    }

    /**
     * Execute stable merger.
     */
    protected function executeMerge(Stable $primary, Stable $secondary, Carbon $date): Stable
    {
        // Transfer all members from secondary to primary
        $this->transferAllMembers($secondary, $primary, $date);

        // Rename primary stable if requested
        if ($this->newStableName) {
            $this->stableRepository->update($primary, new StableData(
                name: $this->newStableName,
                start_date: null,
                tagTeams: collect(),
                wrestlers: collect(),
                managers: collect()
            ));
        }

        // Retire the secondary stable
        StatusTransitionPipeline::retire($secondary, $date)->execute();

        return $primary;
    }

    /**
     * Execute stable split.
     */
    protected function executeSplit(Stable $original, string $newName, Carbon $date): Stable
    {
        // Create new stable
        $newStable = $this->stableRepository->create(new StableData(
            name: $newName,
            start_date: null,
            tagTeams: collect(),
            wrestlers: collect(),
            managers: collect()
        ));

        $this->targetStable = $newStable;

        return $newStable;
    }

    /**
     * Transfer all members from one stable to another.
     */
    protected function transferAllMembers(Stable $from, Stable $to, Carbon $date): void
    {
        // Transfer wrestlers
        foreach ($from->currentWrestlers as $wrestler) {
            $this->stableRepository->removeWrestler($from, $wrestler, $date);
            $this->stableRepository->addWrestler($to, $wrestler, $date);
        }

        // Transfer tag teams
        foreach ($from->currentTagTeams as $tagTeam) {
            $this->stableRepository->removeTagTeam($from, $tagTeam, $date);
            $this->stableRepository->addTagTeam($to, $tagTeam, $date);
        }

        // Note: Managers are not directly transferred between stables
        // Managers are associated with individual wrestlers/tag teams, not stables directly
    }

    /**
     * Execute wrestler transfer operation.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    protected function executeWrestlerTransfer(Collection $wrestlers, Carbon $date): ?Stable
    {
        foreach ($wrestlers as $wrestler) {
            if ($this->sourceStable) {
                $this->stableRepository->removeWrestler($this->sourceStable, $wrestler, $date);
            }
            if ($this->targetStable) {
                $this->stableRepository->addWrestler($this->targetStable, $wrestler, $date);
            }
        }

        return $this->targetStable;
    }

    /**
     * Execute tag team transfer operation.
     *
     * @param  Collection<int, TagTeam>  $tagTeams
     */
    protected function executeTagTeamTransfer(Collection $tagTeams, Carbon $date): ?Stable
    {
        foreach ($tagTeams as $tagTeam) {
            if ($this->sourceStable) {
                $this->stableRepository->removeTagTeam($this->sourceStable, $tagTeam, $date);
            }
            if ($this->targetStable) {
                $this->stableRepository->addTagTeam($this->targetStable, $tagTeam, $date);
            }
        }

        return $this->targetStable;
    }

    /**
     * Execute manager transfer operation.
     *
     * @param  Collection<int, Manager>  $managers
     */
    protected function executeManagerTransfer(Collection $managers, Carbon $date): ?Stable
    {
        foreach ($managers as $manager) {
            if ($this->sourceStable) {
                $this->stableRepository->removeManager($this->sourceStable, $manager, $date);
            }
            if ($this->targetStable) {
                $this->stableRepository->addManager($this->targetStable, $manager, $date);
            }
        }

        return $this->targetStable;
    }

    /**
     * Execute available member transfer.
     */
    protected function executeAvailableMemberTransfer(Carbon $date): ?Stable
    {
        if (! $this->sourceStable) {
            return $this->targetStable;
        }

        // Transfer available wrestlers
        $availableWrestlers = MemberCollectionManager::from($this->sourceStable->currentWrestlers()->get()) // @phpstan-ignore-line argument.type
            ->filterByAvailability()
            ->get();
        $this->executeWrestlerTransfer($availableWrestlers, $date); // @phpstan-ignore-line argument.type

        // Transfer available tag teams
        $availableTagTeams = MemberCollectionManager::from($this->sourceStable->currentTagTeams()->get()) // @phpstan-ignore-line argument.type
            ->filterByAvailability()
            ->get();
        $this->executeTagTeamTransfer($availableTagTeams, $date); // @phpstan-ignore-line argument.type

        // Transfer available managers
        $availableManagers = MemberCollectionManager::from($this->sourceStable->currentManagers()->get()) // @phpstan-ignore-line argument.type
            ->filterByAvailability()
            ->get();
        $this->executeManagerTransfer($availableManagers, $date); // @phpstan-ignore-line argument.type

        return $this->targetStable;
    }

    /**
     * Execute criteria-based transfer.
     *
     * @param  array<string, mixed>  $criteria
     */
    protected function executeCriteriaTransfer(array $criteria, Carbon $date): ?Stable
    {
        if (! $this->sourceStable) {
            return $this->targetStable;
        }

        // Apply criteria to each member type
        $manager = MemberCollectionManager::from($this->sourceStable->currentWrestlers); // @phpstan-ignore-line argument.type
        foreach ($criteria as $method => $value) {
            if (method_exists($manager, $method)) {
                $manager = $manager->{$method}($value);
            }
        }
        $this->executeWrestlerTransfer($manager->get(), $date);

        return $this->targetStable;
    }

    /**
     * Execute cascade strategy.
     *
     * @param  string|array<string, mixed>  $strategy
     */
    protected function executeCascadeStrategy(string|array $strategy, Carbon $date): void
    {
        if (is_string($strategy)) {
            match ($strategy) {
                'employment' => $this->executeEmploymentCascade($date),
                'retire_source' => $this->executeSourceRetirement($date),
                default => null
            };
        } elseif (isset($strategy['type']) && $strategy['type'] === 'suspension') {
            $this->executeSuspensionCascade($strategy['members'], $date);
        }
    }

    /**
     * Execute employment cascade on target stable.
     */
    protected function executeEmploymentCascade(Carbon $date): void
    {
        if (! $this->targetStable) {
            return;
        }

        // Employ all unemployed members
        MemberCollectionManager::from($this->targetStable->currentWrestlers) // @phpstan-ignore-line argument.type
            ->filterByEmploymentStatus('unemployed')
            ->batchEmploy($date);

        MemberCollectionManager::from($this->targetStable->currentTagTeams) // @phpstan-ignore-line argument.type
            ->filterByEmploymentStatus('unemployed')
            ->batchEmploy($date);

        MemberCollectionManager::from($this->targetStable->currentManagers) // @phpstan-ignore-line argument.type
            ->filterByEmploymentStatus('unemployed')
            ->batchEmploy($date);
    }

    /**
     * Execute suspension cascade.
     *
     * @param  array<int, string>  $memberTypes
     */
    protected function executeSuspensionCascade(array $memberTypes, Carbon $date): void
    {
        if (! $this->targetStable) {
            return;
        }

        foreach ($memberTypes as $memberType) {
            $collection = match ($memberType) {
                'wrestlers' => $this->targetStable->currentWrestlers,
                'tag_teams' => $this->targetStable->currentTagTeams,
                // Note: 'managers' removed - stables don't have direct manager relationships
                default => collect()
            };

            MemberCollectionManager::from($collection)
                ->filterBySuspensionStatus('active')
                ->batchSuspend($date);
        }
    }

    /**
     * Execute source stable retirement.
     */
    protected function executeSourceRetirement(Carbon $date): void
    {
        if ($this->sourceStable) {
            StatusTransitionPipeline::retire($this->sourceStable, $date)->execute();
        }
    }

    /**
     * Normalize input to a Collection.
     *
     * @param  Collection<int, Model>|array<int, Model>|Model  $input
     * @return Collection<int, Model>
     */
    protected function normalizeToCollection(Collection|array|Model $input): Collection
    {
        if ($input instanceof Collection) {
            return $input;
        }

        if (is_array($input)) {
            return collect($input);
        }

        return collect([$input]);
    }
}
