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
     * Create a heal transition pipeline.
     *
     * This handles recovery from injury by ending the current injury record.
     */
    public static function heal(Model $entity, ?Carbon $date = null): self
    {
        return new self($entity, 'heal', $date);
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
            'delete' => 'ensureCanBeDeleted',
            'heal' => 'ensureCanBeHealed',
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
            'suspend' => $this->createSuspension(),
            'release' => $this->createRelease(),
            'retire' => $this->createRetirement(),
            'injure' => $this->createInjury(),
            'reinstate' => $this->createReinstatement(),
            'delete' => $this->createDeletion(),
            'heal' => $this->createHeal(),
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
     * Create suspension record using direct Eloquent operations.
     */
    protected function createSuspension(): void
    {
        $table = $this->getTableName('suspensions');
        $data = [
            'started_at' => $this->effectiveDate,
            'ended_at' => null,
        ];

        if ($this->notes) {
            $data['notes'] = $this->notes;
        }

        $this->entity->{$table}()->create($data);
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
     * Create injury record using direct Eloquent operations.
     */
    protected function createInjury(): void
    {
        $table = $this->getTableName('injuries');
        $this->entity->{$table}()->create([
            'started_at' => $this->effectiveDate,
            'ended_at' => null,
        ]);
    }

    /**
     * Create reinstatement record using direct Eloquent operations.
     */
    protected function createReinstatement(): void
    {
        // End current suspension/injury (injuries only apply to entities that
        // can be injured — wrestlers, managers, referees — not tag teams).
        $suspensionTable = $this->getTableName('suspensions');
        $injuryTable = $this->getTableName('injuries');

        $this->entity->{$suspensionTable}()->whereNull('ended_at')->update([
            'ended_at' => $this->effectiveDate,
        ]);

        if (method_exists($this->entity, $injuryTable)) {
            $this->entity->{$injuryTable}()->whereNull('ended_at')->update([
                'ended_at' => $this->effectiveDate,
            ]);
        }
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
     * Handle heal transition by ending the current injury.
     *
     * This ends the active injury record, allowing the entity to return to
     * active competition or duties.
     */
    protected function createHeal(): void
    {
        // End current injury if active
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
