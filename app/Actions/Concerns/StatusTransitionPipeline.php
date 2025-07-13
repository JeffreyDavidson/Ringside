<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Unified status transition pipeline for wrestling promotion entities.
 *
 * This pipeline provides a consistent approach to status changes (employment, suspension,
 * retirement, injury) across all entity types while allowing for entity-specific validation
 * and cascading behaviors.
 *
 * DESIGN PATTERN:
 * Uses Strategy pattern for entity-specific validation and cascading behaviors.
 * Pipeline pattern for consistent execution flow across all status transitions.
 *
 * BUSINESS CONTEXT:
 * Wrestling promotions require consistent status management across wrestlers, managers,
 * referees, and tag teams. Status changes often trigger cascading effects (e.g., employing
 * a wrestler should employ their managers).
 *
 * SUPPORTED TRANSITIONS:
 * - Employment (from unemployed to employed)
 * - Suspension (from employed to suspended)
 * - Release (from employed to released)
 * - Retirement (from active to retired)
 * - Injury (from healthy to injured)
 * - Recovery/Reinstatement (from suspended/injured back to active)
 *
 * @example
 * ```php
 * // Basic usage - employ a wrestler
 * StatusTransitionPipeline::employ($wrestler, $date)
 *     ->withCascade(EmploymentCascadeStrategy::managers())
 *     ->execute();
 *
 * // Complex usage - retire with custom validation
 * StatusTransitionPipeline::retire($tagTeam, $date)
 *     ->withValidation(TagTeamRetirementValidator::class)
 *     ->withCascade(RetirementCascadeStrategy::members())
 *     ->execute();
 * ```
 */
class StatusTransitionPipeline
{
    use ManagesDates;

    protected Model $entity;

    protected string $transition;

    protected Carbon $effectiveDate;

    /** @var array<int, mixed> */
    protected array $cascadeStrategies = [];

    /** @var array<int, mixed> */
    protected array $validationStrategies = [];

    protected ?string $notes = null;

    /**
     * Create a new status transition pipeline instance.
     */
    public function __construct(Model $entity, string $transition, ?Carbon $date = null)
    {
        $this->entity = $entity;
        $this->transition = $transition;
        $this->effectiveDate = $this->getEffectiveDate($date);
    }

    /**
     * Create an employment transition pipeline.
     */
    public static function employ(Model $entity, ?Carbon $date = null): self
    {
        return new self($entity, 'employ', $date);
    }

    /**
     * Create a suspension transition pipeline.
     */
    public static function suspend(Model $entity, ?Carbon $date = null): self
    {
        return new self($entity, 'suspend', $date);
    }

    /**
     * Create a release transition pipeline.
     */
    public static function release(Model $entity, ?Carbon $date = null): self
    {
        return new self($entity, 'release', $date);
    }

    /**
     * Create a retirement transition pipeline.
     */
    public static function retire(Model $entity, ?Carbon $date = null): self
    {
        return new self($entity, 'retire', $date);
    }

    /**
     * Create an injury transition pipeline.
     */
    public static function injure(Model $entity, ?Carbon $date = null): self
    {
        return new self($entity, 'injure', $date);
    }

    /**
     * Create a reinstatement transition pipeline.
     */
    public static function reinstate(Model $entity, ?Carbon $date = null): self
    {
        return new self($entity, 'reinstate', $date);
    }

    /**
     * Add a cascade strategy to execute after the main transition.
     *
     * @param  callable  $strategy  Function that receives (entity, date, transition)
     * @return static
     */
    public function withCascade(callable $strategy): self
    {
        $this->cascadeStrategies[] = $strategy;

        return $this;
    }

    /**
     * Add a validation strategy to execute before the transition.
     *
     * @param  callable  $validator  Function that receives (entity, transition) and throws on failure
     * @return static
     */
    public function withValidation(callable $validator): self
    {
        $this->validationStrategies[] = $validator;

        return $this;
    }

    /**
     * Add notes to the status transition.
     */
    public function withNotes(string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Execute the status transition pipeline.
     *
     * @throws Exception When validation fails or transition cannot be executed
     */
    public function execute(): void
    {
        DB::transaction(function (): void {
            // Step 1: Run validation strategies
            $this->runValidation();

            // Step 2: Execute the core transition
            $this->executeCoreTransition();

            // Step 3: Run cascade strategies
            $this->runCascades();
        });
    }

    /**
     * Run all validation strategies for the transition.
     *
     * @throws Exception When any validation fails
     */
    protected function runValidation(): void
    {
        // Run default entity validation (e.g., ensureCanBeEmployed)
        $this->runDefaultValidation();

        // Run custom validation strategies
        foreach ($this->validationStrategies as $validator) {
            $validator($this->entity, $this->transition);
        }
    }

    /**
     * Run the default validation method on the entity.
     */
    protected function runDefaultValidation(): void
    {
        $method = $this->getDefaultValidationMethod();

        if (method_exists($this->entity, $method)) {
            $this->entity->{$method}();
        }
    }

    /**
     * Get the default validation method name for the transition.
     */
    protected function getDefaultValidationMethod(): string
    {
        return match ($this->transition) {
            'employ' => 'ensureCanBeEmployed',
            'suspend' => 'ensureCanBeSuspended',
            'release' => 'ensureCanBeReleased',
            'retire' => 'ensureCanBeRetired',
            'injure' => 'ensureCanBeInjured',
            'reinstate' => 'ensureCanBeReinstated',
            default => throw new InvalidArgumentException("Unknown transition: {$this->transition}")
        };
    }

    /**
     * Execute the core status transition.
     */
    protected function executeCoreTransition(): void
    {
        $repository = $this->getRepository();
        $method = $this->getRepositoryMethod();

        // Handle transitions that require ending existing status
        $this->handleStatusEnding();

        // Execute the main transition
        if (method_exists($repository, $method)) {
            if ($this->notes) {
                $repository->{$method}($this->entity, $this->effectiveDate, $this->notes);
            } else {
                $repository->{$method}($this->entity, $this->effectiveDate);
            }
        } else {
            throw new InvalidArgumentException("Repository method {$method} not found");
        }
    }

    /**
     * Handle ending existing status before new transition (e.g., end retirement before employment).
     */
    protected function handleStatusEnding(): void
    {
        $repository = $this->getRepository();

        // Employment requires ending retirement
        if ($this->transition === 'employ' && method_exists($this->entity, 'isRetired') && $this->entity->isRetired()) {
            $repository->endRetirement($this->entity, $this->effectiveDate);
        }

        // Add other status ending logic as needed
    }

    /**
     * Get the repository method name for the transition.
     */
    protected function getRepositoryMethod(): string
    {
        return match ($this->transition) {
            'employ' => 'createEmployment',
            'suspend' => 'createSuspension',
            'release' => 'createRelease',
            'retire' => 'createRetirement',
            'injure' => 'createInjury',
            'reinstate' => 'createReinstatement',
            default => throw new InvalidArgumentException("Unknown transition: {$this->transition}")
        };
    }

    /**
     * Get the repository instance for the entity.
     */
    protected function getRepository(): mixed
    {
        $entityClass = get_class($this->entity);
        $baseName = class_basename($entityClass);

        // Handle the case where models are in subdirectories but repositories are not
        // App\Models\Wrestlers\Wrestler -> App\Repositories\WrestlerRepository
        $namespace = 'App\\Repositories\\';
        $repositoryClass = $namespace.$baseName.'Repository';

        if (! class_exists($repositoryClass)) {
            throw new InvalidArgumentException("Repository {$repositoryClass} not found");
        }

        return app($repositoryClass);
    }

    /**
     * Run all cascade strategies after the main transition.
     */
    protected function runCascades(): void
    {
        foreach ($this->cascadeStrategies as $strategy) {
            $strategy($this->entity, $this->effectiveDate, $this->transition);
        }
    }
}
