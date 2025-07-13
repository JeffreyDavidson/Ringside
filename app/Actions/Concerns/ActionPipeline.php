<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Complex workflow composition pipeline for wrestling promotion operations.
 *
 * This pipeline allows chaining multiple different actions and workflows together
 * into sophisticated business operations. It builds on top of all other orchestration
 * components to provide the highest level of workflow composition.
 *
 * BUSINESS CONTEXT:
 * Wrestling promotions often need to execute complex workflows that involve multiple
 * entity types and operations. Examples include roster overhauls, event preparations,
 * stable restructuring, and championship changes that require coordinated actions
 * across multiple entities.
 *
 * DESIGN PATTERN:
 * Pipeline pattern - Chainable operations with consistent execution flow.
 * Command pattern - Each operation is encapsulated as an executable command.
 * Strategy pattern - Different execution strategies for different workflow types.
 *
 * @example
 * ```php
 * // Complex stable merger with employment and championship transfer
 * ActionPipeline::create()
 *     ->stableMerger($primaryStable, $secondaryStable, 'New Stable Name')
 *     ->employAllMembers($date)
 *     ->transferChampionships($titleIds, $newChampions)
 *     ->execute();
 *
 * // Roster overhaul workflow
 * ActionPipeline::create()
 *     ->releaseMembers($wrestlersToRelease)
 *     ->retireStables($stablesToRetire)
 *     ->employNewTalent($newSignings)
 *     ->createNewStables($newStableData)
 *     ->execute();
 * ```
 */
class ActionPipeline
{
    use AsAction;

    /** @var array<int, array<string, mixed>> */
    protected array $operations = [];

    /** @var array<int, array<string, mixed>> */
    protected array $rollbackOperations = [];

    protected ?Carbon $defaultDate = null;

    protected bool $continueOnError = false;

    /** @var array<int, mixed> */
    protected array $results = [];

    /** @var array<int, Exception> */
    protected array $errors = [];

    /**
     * Create a new action pipeline.
     */
    public function __construct()
    {
        // Initialize pipeline
    }

    /**
     * Create a new action pipeline instance.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Set the default date for all operations in the pipeline.
     */
    public function withDefaultDate(Carbon $date): self
    {
        $this->defaultDate = $date;

        return $this;
    }

    /**
     * Configure pipeline to continue execution even if individual operations fail.
     */
    public function continueOnError(bool $continue = true): self
    {
        $this->continueOnError = $continue;

        return $this;
    }

    /**
     * Add a stable merger operation to the pipeline.
     *
     * @param  Model  $primaryStable  The stable that will absorb members
     * @param  Model  $secondaryStable  The stable whose members will be transferred
     * @param  string|null  $newName  Optional new name for merged stable
     * @return static
     */
    public function stableMerger(Model $primaryStable, Model $secondaryStable, ?string $newName = null): self
    {
        $this->operations[] = [
            'type' => 'stable_merger',
            'primary' => $primaryStable,
            'secondary' => $secondaryStable,
            'newName' => $newName,
            'rollback' => function () use ($primaryStable, $secondaryStable) {
                // Complex rollback would require restoring original stable memberships
                $this->rollbackOperations[] = ['type' => 'restore_stable_memberships', 'stables' => [$primaryStable, $secondaryStable]];
            },
        ];

        return $this;
    }

    /**
     * Add a stable split operation to the pipeline.
     *
     * @param  Model  $originalStable  The stable to split
     * @param  string  $newStableName  Name for the new stable
     * @param  array<string, mixed>  $membersForNewStable  Members to transfer to new stable
     * @return static
     */
    public function stableSplit(Model $originalStable, string $newStableName, array $membersForNewStable): self
    {
        $this->operations[] = [
            'type' => 'stable_split',
            'original' => $originalStable,
            'newName' => $newStableName,
            'members' => $membersForNewStable,
            'rollback' => function ($result) {
                // Rollback would delete the new stable and restore members to original
                if (isset($result['newStable'])) {
                    $this->rollbackOperations[] = ['type' => 'delete_stable', 'stable' => $result['newStable']];
                }
            },
        ];

        return $this;
    }

    /**
     * Add a batch employment operation to the pipeline.
     *
     * @param  iterable<Model>  $entities  Entities to employ
     * @param  Carbon|null  $date  Employment date
     * @return static
     */
    public function employMembers(iterable $entities, ?Carbon $date = null): self
    {
        $this->operations[] = [
            'type' => 'batch_employ',
            'entities' => $entities,
            'date' => $date ?? $this->defaultDate,
            'rollback' => function () use ($entities, $date) {
                // Rollback would release all employed entities
                $this->rollbackOperations[] = ['type' => 'batch_release', 'entities' => $entities, 'date' => $date];
            },
        ];

        return $this;
    }

    /**
     * Add a batch release operation to the pipeline.
     *
     * @param  iterable<Model>  $entities  Entities to release
     * @param  Carbon|null  $date  Release date
     * @return static
     */
    public function releaseMembers(iterable $entities, ?Carbon $date = null): self
    {
        $this->operations[] = [
            'type' => 'batch_release',
            'entities' => $entities,
            'date' => $date ?? $this->defaultDate,
            'rollback' => function () use ($entities, $date) {
                // Rollback would re-employ all released entities
                $this->rollbackOperations[] = ['type' => 'batch_employ', 'entities' => $entities, 'date' => $date];
            },
        ];

        return $this;
    }

    /**
     * Add a batch retirement operation to the pipeline.
     *
     * @param  iterable<Model>  $entities  Entities to retire
     * @param  Carbon|null  $date  Retirement date
     * @return static
     */
    public function retireMembers(iterable $entities, ?Carbon $date = null): self
    {
        $this->operations[] = [
            'type' => 'batch_retire',
            'entities' => $entities,
            'date' => $date ?? $this->defaultDate,
            'rollback' => function () use ($entities, $date) {
                // Rollback would unretire all entities
                $this->rollbackOperations[] = ['type' => 'batch_unretire', 'entities' => $entities, 'date' => $date];
            },
        ];

        return $this;
    }

    /**
     * Add a batch suspension operation to the pipeline.
     *
     * @param  iterable<Model>  $entities  Entities to suspend
     * @param  Carbon|null  $date  Suspension date
     * @return static
     */
    public function suspendMembers(iterable $entities, ?Carbon $date = null): self
    {
        $this->operations[] = [
            'type' => 'batch_suspend',
            'entities' => $entities,
            'date' => $date ?? $this->defaultDate,
            'rollback' => function () use ($entities, $date) {
                // Rollback would reinstate all entities
                $this->rollbackOperations[] = ['type' => 'batch_reinstate', 'entities' => $entities, 'date' => $date];
            },
        ];

        return $this;
    }

    /**
     * Add member collection filtering and batch operations.
     *
     * @param  Collection<int, Model>  $collection  Source collection to filter
     * @param  array<string, mixed>  $filterCriteria  Filtering criteria
     * @param  string  $operation  Operation to perform ('employ', 'release', 'suspend', 'retire')
     * @param  Carbon|null  $date  Operation date
     * @return static
     */
    public function filterAndBatch(Collection $collection, array $filterCriteria, string $operation, ?Carbon $date = null): self
    {
        $this->operations[] = [
            'type' => 'filter_and_batch',
            'collection' => $collection,
            'criteria' => $filterCriteria,
            'operation' => $operation,
            'date' => $date ?? $this->defaultDate,
        ];

        return $this;
    }

    /**
     * Add a custom action to the pipeline.
     *
     * @param  callable  $action  Custom action to execute
     * @param  callable|null  $rollback  Optional rollback action
     * @return static
     */
    public function customAction(callable $action, ?callable $rollback = null): self
    {
        $this->operations[] = [
            'type' => 'custom',
            'action' => $action,
            'rollback' => $rollback,
        ];

        return $this;
    }

    /**
     * Add an orchestrated stable membership operation.
     *
     * @param  callable  $orchestratorCallback  Callback that receives StableMembershipOrchestrator
     * @return static
     */
    public function stableOrchestration(callable $orchestratorCallback): self
    {
        $this->operations[] = [
            'type' => 'stable_orchestration',
            'callback' => $orchestratorCallback,
        ];

        return $this;
    }

    /**
     * Execute all operations in the pipeline within a database transaction.
     *
     * @return array<string, mixed> Results from all operations
     *
     * @throws Exception When operations fail and continueOnError is false
     */
    public function execute(): array
    {
        return DB::transaction(function (): array {
            $this->results = [];
            $this->errors = [];

            foreach ($this->operations as $index => $operation) {
                try {
                    $result = $this->executeOperation($operation);
                    $this->results[$index] = $result;
                } catch (Exception $e) {
                    $this->errors[$index] = $e;

                    if (! $this->continueOnError) {
                        // Rollback executed operations
                        $this->rollbackExecutedOperations($index);
                        throw $e;
                    }
                }
            }

            return [
                'results' => $this->results,
                'errors' => $this->errors,
                'success' => empty($this->errors),
            ];
        });
    }

    /**
     * Execute a single operation.
     *
     * @param  array<string, mixed>  $operation  The operation to execute
     * @return mixed Operation result
     */
    protected function executeOperation(array $operation): mixed
    {
        return match ($operation['type']) {
            'stable_merger' => $this->executeStableMerger($operation),
            'stable_split' => $this->executeStableSplit($operation),
            'batch_employ' => $this->executeBatchEmploy($operation),
            'batch_release' => $this->executeBatchRelease($operation),
            'batch_retire' => $this->executeBatchRetire($operation),
            'batch_suspend' => $this->executeBatchSuspend($operation),
            'batch_reinstate' => $this->executeBatchReinstate($operation),
            'filter_and_batch' => $this->executeFilterAndBatch($operation),
            'stable_orchestration' => $this->executeStableOrchestration($operation),
            'custom' => $this->executeCustomAction($operation),
            default => throw new InvalidArgumentException("Unknown operation type: {$operation['type']}")
        };
    }

    /**
     * Execute stable merger operation.
     *
     * @param  array<string, mixed>  $operation
     */
    protected function executeStableMerger(array $operation): Model
    {
        $orchestrator = StableMembershipOrchestrator::mergeStables(
            $operation['primary'],
            $operation['secondary'],
            $operation['newName']
        );

        if ($this->defaultDate) {
            $orchestrator = $orchestrator->onDate($this->defaultDate);
        }

        return $orchestrator->execute();
    }

    /**
     * Execute stable split operation.
     *
     * @param  array<string, mixed>  $operation
     */
    protected function executeStableSplit(array $operation): Model
    {
        $orchestrator = StableMembershipOrchestrator::splitStable(
            $operation['original'],
            $operation['newName']
        );

        // Add member transfers based on the members array
        foreach ($operation['members'] as $memberType => $members) {
            $method = 'transfer'.ucfirst($memberType);
            if (method_exists($orchestrator, $method)) {
                $orchestrator = $orchestrator->{$method}($members);
            }
        }

        if ($this->defaultDate) {
            $orchestrator = $orchestrator->onDate($this->defaultDate);
        }

        return $orchestrator->execute();
    }

    /**
     * Execute batch employment operation.
     *
     * @param  array<string, mixed>  $operation
     */
    protected function executeBatchEmploy(array $operation): void
    {
        UnifiedEmployAction::batch($operation['entities'], $operation['date']);
    }

    /**
     * Execute batch release operation.
     *
     * @param  array<string, mixed>  $operation
     */
    protected function executeBatchRelease(array $operation): void
    {
        foreach ($operation['entities'] as $entity) {
            StatusTransitionPipeline::release($entity, $operation['date'])->execute();
        }
    }

    /**
     * Execute batch retirement operation.
     *
     * @param  array<string, mixed>  $operation
     */
    protected function executeBatchRetire(array $operation): void
    {
        foreach ($operation['entities'] as $entity) {
            StatusTransitionPipeline::retire($entity, $operation['date'])->execute();
        }
    }

    /**
     * Execute batch suspension operation.
     *
     * @param  array<string, mixed>  $operation
     */
    protected function executeBatchSuspend(array $operation): void
    {
        foreach ($operation['entities'] as $entity) {
            StatusTransitionPipeline::suspend($entity, $operation['date'])->execute();
        }
    }

    /**
     * Execute batch reinstatement operation.
     *
     * @param  array<string, mixed>  $operation
     */
    protected function executeBatchReinstate(array $operation): void
    {
        foreach ($operation['entities'] as $entity) {
            StatusTransitionPipeline::reinstate($entity, $operation['date'])->execute();
        }
    }

    /**
     * Execute filter and batch operation.
     *
     * @param  array<string, mixed>  $operation
     * @return array<string, mixed>
     */
    protected function executeFilterAndBatch(array $operation): array
    {
        $manager = MemberCollectionManager::from($operation['collection']);

        // Apply filter criteria
        foreach ($operation['criteria'] as $method => $value) {
            if (method_exists($manager, $method)) {
                $manager = $manager->{$method}($value);
            }
        }

        // Execute batch operation
        $batchMethod = 'batch'.ucfirst($operation['operation']);
        if (method_exists($manager, $batchMethod)) {
            $manager->{$batchMethod}($operation['date']);
        }

        return $manager->getStatistics();
    }

    /**
     * Execute stable orchestration operation.
     *
     * @param  array<string, mixed>  $operation
     */
    protected function executeStableOrchestration(array $operation): mixed
    {
        $orchestrator = app(StableMembershipOrchestrator::class);

        return $operation['callback']($orchestrator);
    }

    /**
     * Execute custom action.
     *
     * @param  array<string, mixed>  $operation
     */
    protected function executeCustomAction(array $operation): mixed
    {
        return $operation['action']();
    }

    /**
     * Rollback all executed operations up to the failed operation.
     */
    protected function rollbackExecutedOperations(int $failedIndex): void
    {
        for ($i = $failedIndex - 1; $i >= 0; $i--) {
            if (isset($this->operations[$i]['rollback'])) {
                try {
                    $rollback = $this->operations[$i]['rollback'];
                    if (is_callable($rollback)) {
                        $rollback($this->results[$i] ?? null);
                    }
                } catch (Exception $e) {
                    // Log rollback failures but don't throw
                    logger()->error('ActionPipeline rollback failed', [
                        'operation_index' => $i,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Get the results from the last execution.
     *
     * @return array<int, mixed>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Get any errors from the last execution.
     *
     * @return array<int, Exception>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if the last execution was successful.
     */
    public function wasSuccessful(): bool
    {
        return empty($this->errors);
    }
}
