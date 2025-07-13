<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use App\Enums\Shared\RosterMemberType;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Standardized collection management for wrestling promotion entity members.
 *
 * This manager provides consistent filtering, batching, and operation patterns
 * for collections of wrestlers, managers, tag teams, and other entities across
 * the wrestling promotion system.
 *
 * BUSINESS CONTEXT:
 * Wrestling promotions frequently need to perform bulk operations on groups
 * of talent (employing all wrestlers in a stable, suspending tag team members,
 * etc.). This manager standardizes these patterns.
 *
 * DESIGN PATTERN:
 * Builder pattern - Chainable methods for building complex filter criteria.
 * Strategy pattern - Different filtering strategies for different use cases.
 *
 * @example
 * ```php
 * // Filter and employ all unemployed wrestlers in a stable
 * MemberCollectionManager::from($stable->currentWrestlers)
 *     ->filterByEmploymentStatus('unemployed')
 *     ->batchEmploy($date);
 *
 * // Get suspended managers who aren't injured
 * $suspendedHealthyManagers = MemberCollectionManager::from($managers)
 *     ->filterBySuspensionStatus('suspended')
 *     ->filterByInjuryStatus('healthy')
 *     ->get();
 * ```
 */
class MemberCollectionManager
{
    /** @var Collection<int, Model> */
    protected Collection $collection;

    /** @var array<string, callable> */
    protected array $filters = [];

    /** @var array<int, array<string, mixed>> */
    protected array $operations = [];

    /**
     * Create a new member collection manager instance.
     *
     * @param  Collection<int, Model>  $collection
     */
    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Create a manager instance from a collection.
     *
     * @param  Collection<int, Model>  $collection
     */
    public static function from(Collection $collection): self
    {
        return new self($collection);
    }

    /**
     * Filter members by employment status.
     *
     * @param  string  $status  'employed', 'unemployed', 'released', or 'any'
     * @return static
     */
    public function filterByEmploymentStatus(string $status): self
    {
        if ($status === 'any') {
            return $this;
        }

        $filter = match ($status) {
            'employed' => fn (Model $entity) => RosterMemberType::hasCapability($entity, 'employed') && ($entity instanceof Employable) && $entity->isEmployed(),
            'unemployed' => fn (Model $entity) => RosterMemberType::hasCapability($entity, 'employed') && ($entity instanceof Employable) && ! $entity->isEmployed(),
            'released' => fn (Model $entity) => RosterMemberType::hasCapability($entity, 'employed') && ($entity instanceof Employable) && $entity->isReleased(),
            default => throw new InvalidArgumentException("Invalid employment status: {$status}")
        };

        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Filter members by suspension status.
     *
     * @param  string  $status  'suspended', 'active', or 'any'
     * @return static
     */
    public function filterBySuspensionStatus(string $status): self
    {
        if ($status === 'any') {
            return $this;
        }

        $filter = match ($status) {
            'suspended' => fn (Model $entity) => RosterMemberType::hasCapability($entity, 'suspended') && ($entity instanceof Suspendable) && $entity->isSuspended(),
            'active' => fn (Model $entity) => RosterMemberType::hasCapability($entity, 'suspended') && ($entity instanceof Suspendable) && ! $entity->isSuspended(),
            default => throw new InvalidArgumentException("Invalid suspension status: {$status}")
        };

        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Filter members by injury status.
     *
     * @param  string  $status  'injured', 'healthy', or 'any'
     * @return static
     */
    public function filterByInjuryStatus(string $status): self
    {
        if ($status === 'any') {
            return $this;
        }

        $filter = match ($status) {
            'injured' => fn (Model $entity) => RosterMemberType::hasCapability($entity, 'injured') && ($entity instanceof Injurable) && $entity->isInjured(),
            'healthy' => fn (Model $entity) => RosterMemberType::hasCapability($entity, 'injured') && ($entity instanceof Injurable) && ! $entity->isInjured(),
            default => throw new InvalidArgumentException("Invalid injury status: {$status}")
        };

        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Filter members by retirement status.
     *
     * @param  string  $status  'retired', 'active', or 'any'
     * @return static
     */
    public function filterByRetirementStatus(string $status): self
    {
        if ($status === 'any') {
            return $this;
        }

        $filter = match ($status) {
            'retired' => fn (Model $entity) => RosterMemberType::hasCapability($entity, 'retired') && ($entity instanceof Retirable) && $entity->isRetired(),
            'active' => fn (Model $entity) => RosterMemberType::hasCapability($entity, 'retired') && ($entity instanceof Retirable) && ! $entity->isRetired(),
            default => throw new InvalidArgumentException("Invalid retirement status: {$status}")
        };

        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Filter members by availability for competition.
     *
     * Available means: employed, not suspended, not injured, not retired.
     *
     * @param  bool  $availableOnly  True to only include available members
     * @return static
     */
    public function filterByAvailability(bool $availableOnly = true): self
    {
        if (! $availableOnly) {
            return $this;
        }

        $this->filters[] = function (Model $entity): bool {
            // Must be employed
            if (RosterMemberType::hasCapability($entity, 'employed') && ($entity instanceof Employable) && ! $entity->isEmployed()) {
                return false;
            }

            // Must not be suspended
            if (RosterMemberType::hasCapability($entity, 'suspended') && ($entity instanceof Suspendable) && $entity->isSuspended()) {
                return false;
            }

            // Must not be injured
            if (RosterMemberType::hasCapability($entity, 'injured') && ($entity instanceof Injurable) && $entity->isInjured()) {
                return false;
            }

            // Must not be retired
            if (RosterMemberType::hasCapability($entity, 'retired') && ($entity instanceof Retirable) && $entity->isRetired()) {
                return false;
            }

            return true;
        };

        return $this;
    }

    /**
     * Filter members by entity type.
     *
     * @param  string|array<int, string>  $types  Class name(s) or type string(s)
     * @return static
     */
    public function filterByType(string|array $types): self
    {
        $types = is_array($types) ? $types : [$types];

        $this->filters[] = function (Model $entity) use ($types): bool {
            $entityClass = get_class($entity);
            $entityType = class_basename($entityClass);

            foreach ($types as $type) {
                // Match by full class name
                if ($entityClass === $type) {
                    return true;
                }

                // Match by class basename
                if ($entityType === $type) {
                    return true;
                }

                // Match by lowercase type name
                if (mb_strtolower($entityType) === mb_strtolower($type)) {
                    return true;
                }
            }

            return false;
        };

        return $this;
    }

    /**
     * Filter members using a custom callback.
     *
     * @param  callable  $callback  Function that receives entity and returns bool
     * @return static
     */
    public function filterBy(callable $callback): self
    {
        $this->filters[] = $callback;

        return $this;
    }

    /**
     * Apply all filters and get the resulting collection.
     *
     * @return Collection<int, Model>
     */
    public function get(): Collection
    {
        $filtered = $this->collection;

        foreach ($this->filters as $filter) {
            $filtered = $filtered->filter($filter);
        }

        return $filtered;
    }

    /**
     * Count the members after applying filters.
     */
    public function count(): int
    {
        return $this->get()->count();
    }

    /**
     * Check if any members exist after applying filters.
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Get the first member after applying filters.
     */
    public function first(): ?Model
    {
        return $this->get()->first();
    }

    /**
     * Batch employ all filtered members.
     *
     * @param  Carbon|null  $date  Employment date
     * @param  string|null  $notes  Optional notes
     */
    public function batchEmploy(?Carbon $date = null, ?string $notes = null): void
    {
        $entities = $this->get();
        UnifiedEmployAction::batch($entities, $date, $notes);
    }

    /**
     * Batch suspend all filtered members.
     *
     * @param  Carbon|null  $date  Suspension date
     * @param  string|null  $notes  Optional notes
     */
    public function batchSuspend(?Carbon $date = null, ?string $notes = null): void
    {
        $entities = $this->get();

        foreach ($entities as $entity) {
            StatusTransitionPipeline::suspend($entity, $date)
                ->withNotes($notes ?? '')
                ->execute();
        }
    }

    /**
     * Batch retire all filtered members.
     *
     * @param  Carbon|null  $date  Retirement date
     * @param  string|null  $notes  Optional notes
     */
    public function batchRetire(?Carbon $date = null, ?string $notes = null): void
    {
        $entities = $this->get();

        foreach ($entities as $entity) {
            StatusTransitionPipeline::retire($entity, $date)
                ->withNotes($notes ?? '')
                ->execute();
        }
    }

    /**
     * Batch injure all filtered members.
     *
     * @param  Carbon|null  $date  Injury date
     * @param  string|null  $notes  Optional notes
     */
    public function batchInjure(?Carbon $date = null, ?string $notes = null): void
    {
        $entities = $this->get();

        foreach ($entities as $entity) {
            if (RosterMemberType::hasCapability($entity, 'injured')) {
                StatusTransitionPipeline::injure($entity, $date)
                    ->withNotes($notes ?? '')
                    ->execute();
            }
        }
    }

    /**
     * Batch reinstate all filtered members.
     *
     * @param  Carbon|null  $date  Reinstatement date
     * @param  string|null  $notes  Optional notes
     */
    public function batchReinstate(?Carbon $date = null, ?string $notes = null): void
    {
        $entities = $this->get();

        foreach ($entities as $entity) {
            StatusTransitionPipeline::reinstate($entity, $date)
                ->withNotes($notes ?? '')
                ->execute();
        }
    }

    /**
     * Group filtered members by their current status.
     *
     * @return array{employed: Collection<int, Model>, unemployed: Collection<int, Model>, suspended: Collection<int, Model>, injured: Collection<int, Model>, retired: Collection<int, Model>, available: Collection<int, Model>}
     */
    public function groupByStatus(): array
    {
        $entities = $this->get();

        $employed = $entities->filter(fn ($e) => RosterMemberType::hasCapability($e, 'employed') && ($e instanceof Employable) && $e->isEmployed());
        $unemployed = $entities->filter(fn ($e) => RosterMemberType::hasCapability($e, 'employed') && ($e instanceof Employable) && ! $e->isEmployed());
        $suspended = $entities->filter(fn ($e) => RosterMemberType::hasCapability($e, 'suspended') && ($e instanceof Suspendable) && $e->isSuspended());
        $injured = $entities->filter(fn ($e) => RosterMemberType::hasCapability($e, 'injured') && ($e instanceof Injurable) && $e->isInjured());
        $retired = $entities->filter(fn ($e) => RosterMemberType::hasCapability($e, 'retired') && ($e instanceof Retirable) && $e->isRetired());
        $available = $entities->filter(function ($entity) {
            return (! RosterMemberType::hasCapability($entity, 'employed') || (($entity instanceof Employable) && $entity->isEmployed())) &&
                   (! RosterMemberType::hasCapability($entity, 'suspended') || ! (($entity instanceof Suspendable) && $entity->isSuspended())) &&
                   (! RosterMemberType::hasCapability($entity, 'injured') || ! (($entity instanceof Injurable) && $entity->isInjured())) &&
                   (! RosterMemberType::hasCapability($entity, 'retired') || ! (($entity instanceof Retirable) && $entity->isRetired()));
        });

        return [ // @phpstan-ignore-line return.type
            'employed' => $employed,
            'unemployed' => $unemployed,
            'suspended' => $suspended,
            'injured' => $injured,
            'retired' => $retired,
            'available' => $available,
        ];
    }

    /**
     * Get statistics about the filtered collection.
     *
     * @return array<string, int> Statistics including counts by status
     */
    public function getStatistics(): array
    {
        $grouped = $this->groupByStatus();

        return [
            'total' => $this->count(),
            'employed' => $grouped['employed']->count(),
            'unemployed' => $grouped['unemployed']->count(),
            'suspended' => $grouped['suspended']->count(),
            'injured' => $grouped['injured']->count(),
            'retired' => $grouped['retired']->count(),
            'available' => $grouped['available']->count(),
        ];
    }
}
