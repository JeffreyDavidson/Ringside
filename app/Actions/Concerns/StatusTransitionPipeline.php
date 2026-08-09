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
 * This pipeline provides a consistent approach to the remaining status changes (employment,
 * release, retirement, deletion, and unretirement) while allowing for entity-specific validation
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
 * - Release (from employed to released)
 * - Retirement (from active to retired)
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
     * Create a deletion transition pipeline.
     *
     * This handles status cleanup before entity deletion, ending any active
     * statuses (employment, retirement, suspension, injury) as appropriate.
     */
    public static function delete(Model $entity, ?Carbon $date = null): self
    {
        return new self($entity, 'delete', $date);
    }

    /**
     * Create an unretire transition pipeline.
     *
     * This handles coming out of retirement by ending the current retirement
     * record and updating status to unemployed.
     */
    public static function unretire(Model $entity, ?Carbon $date = null): self
    {
        return new self($entity, 'unretire', $date);
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
            'release' => 'ensureCanBeReleased',
            'retire' => 'ensureCanBeRetired',
            'delete' => 'ensureCanBeDeleted',
            'unretire' => 'ensureCanBeUnretired',
            default => throw new InvalidArgumentException("Unknown transition: {$this->transition}")
        };
    }

    /**
     * Execute the core status transition.
     */
    protected function executeCoreTransition(): void
    {
        // Handle transitions that require ending existing status
        $this->handleStatusEnding();

        // Execute the main transition using direct Eloquent operations
        match ($this->transition) {
            'employ' => $this->createEmployment(),
            'release' => $this->createRelease(),
            'retire' => $this->createRetirement(),
            'delete' => $this->createDeletion(),
            'unretire' => $this->endRetirement(),
            default => throw new InvalidArgumentException("Unknown transition: {$this->transition}")
        };
    }

    /**
     * Handle ending existing status before new transition (e.g., end retirement before employment).
     */
    protected function handleStatusEnding(): void
    {
        // Employment requires ending retirement
        if ($this->transition === 'employ' && method_exists($this->entity, 'isRetired') && $this->entity->isRetired()) {
            $this->endRetirement();
        }

        // Add other status ending logic as needed
    }

    /**
     * Create employment record using direct Eloquent operations.
     */
    protected function createEmployment(): void
    {
        $table = $this->getTableName('employments');
        $this->entity->{$table}()->create([
            'started_at' => $this->effectiveDate,
            'ended_at' => null,
        ]);
    }

    /**
     * Create release record using direct Eloquent operations.
     */
    protected function createRelease(): void
    {
        // End current employment
        $employmentTable = $this->getTableName('employments');
        $this->entity->{$employmentTable}()->whereNull('ended_at')->update([
            'ended_at' => $this->effectiveDate,
        ]);

        if (method_exists($this->entity, 'isSuspended') && $this->entity->isSuspended()) {
            $suspensionTable = $this->getTableName('suspensions');
            $this->entity->{$suspensionTable}()->whereNull('ended_at')->update([
                'ended_at' => $this->effectiveDate,
            ]);
        }

        if (method_exists($this->entity, 'isInjured') && $this->entity->isInjured()) {
            $injuryTable = $this->getTableName('injuries');
            $this->entity->{$injuryTable}()->whereNull('ended_at')->update([
                'ended_at' => $this->effectiveDate,
            ]);
        }
    }

    /**
     * Create retirement record using direct Eloquent operations.
     */
    protected function createRetirement(): void
    {
        // End any active employment so the entity is no longer "employed" once retired.
        if (method_exists($this->entity, 'isEmployed') && $this->entity->isEmployed()) {
            $employmentTable = $this->getTableName('employments');
            $this->entity->{$employmentTable}()->whereNull('ended_at')->update([
                'ended_at' => $this->effectiveDate,
            ]);
        }

        if (method_exists($this->entity, 'isSuspended') && $this->entity->isSuspended()) {
            $suspensionTable = $this->getTableName('suspensions');
            $this->entity->{$suspensionTable}()->whereNull('ended_at')->update([
                'ended_at' => $this->effectiveDate,
            ]);
        }

        if (method_exists($this->entity, 'isInjured') && $this->entity->isInjured()) {
            $injuryTable = $this->getTableName('injuries');
            $this->entity->{$injuryTable}()->whereNull('ended_at')->update([
                'ended_at' => $this->effectiveDate,
            ]);
        }

        $table = $this->getTableName('retirements');
        $this->entity->{$table}()->create([
            'started_at' => $this->effectiveDate,
            'ended_at' => null,
        ]);
    }

    /**
     * Handle deletion transition by ending all active statuses.
     *
     * This prepares the entity for deletion by ending any active employment,
     * retirement, suspension, or injury records.
     */
    protected function createDeletion(): void
    {
        // End employment if active
        if (method_exists($this->entity, 'isEmployed') && $this->entity->isEmployed()) {
            $employmentTable = $this->getTableName('employments');
            $this->entity->{$employmentTable}()->whereNull('ended_at')->update([
                'ended_at' => $this->effectiveDate,
            ]);
        }

        // End retirement if active
        if (method_exists($this->entity, 'isRetired') && $this->entity->isRetired()) {
            $this->endRetirement();
        }

        // End suspension if active
        if (method_exists($this->entity, 'isSuspended') && $this->entity->isSuspended()) {
            $suspensionTable = $this->getTableName('suspensions');
            $this->entity->{$suspensionTable}()->whereNull('ended_at')->update([
                'ended_at' => $this->effectiveDate,
            ]);
        }

        // End injury if active
        if (method_exists($this->entity, 'isInjured') && $this->entity->isInjured()) {
            $injuryTable = $this->getTableName('injuries');
            $this->entity->{$injuryTable}()->whereNull('ended_at')->update([
                'ended_at' => $this->effectiveDate,
            ]);
        }
    }

    /**
     * End retirement using direct Eloquent operations.
     */
    protected function endRetirement(): void
    {
        $table = $this->getTableName('retirements');
        $this->entity->{$table}()->whereNull('ended_at')->update([
            'ended_at' => $this->effectiveDate,
        ]);
    }

    /**
     * Get the relationship name for the entity type.
     */
    protected function getTableName(string $type): string
    {
        // Return just the type (e.g., 'employments', 'suspensions', etc.)
        // Models define their own relationships
        return $type;
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
