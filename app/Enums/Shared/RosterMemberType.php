<?php

declare(strict_types=1);

namespace App\Enums\Shared;

use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Enum representing different types of roster members in the wrestling promotion.
 *
 * This enum defines the various types of entities that can be part of the roster,
 * including individual performers (Wrestlers, Managers, Referees), teams (Tag Teams),
 * and championships (Titles). Each type has different capabilities and business rules.
 *
 * @example
 * ```php
 * // Check if a wrestler can be injured
 * if (RosterMemberType::WRESTLER->canBeInjured()) {
 *     // Handle injury logic
 * }
 *
 * // Get type from model
 * $type = RosterMemberType::fromModel($wrestler); // RosterMemberType::WRESTLER
 * ```
 */
enum RosterMemberType: string
{
    case WRESTLER = 'wrestler';
    case MANAGER = 'manager';
    case REFEREE = 'referee';
    case TAG_TEAM = 'tag_team';
    case TITLE = 'title';
    case STABLE = 'stable';

    /**
     * Create enum instance from a model.
     *
     * Automatically detects the roster member type based on the model class
     * and returns the appropriate enum value.
     *
     * @param  Model  $model  The model to detect type for
     * @return self The corresponding enum value
     *
     * @throws InvalidArgumentException If the model type is not a supported roster member
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $type = RosterMemberType::fromModel($wrestler); // RosterMemberType::WRESTLER
     *
     * $tagTeam = TagTeam::find(1);
     * $type = RosterMemberType::fromModel($tagTeam); // RosterMemberType::TAG_TEAM
     * ```
     */
    public static function fromModel(Model $model): self
    {
        return match (get_class($model)) {
            Wrestler::class => self::WRESTLER,
            Manager::class => self::MANAGER,
            Referee::class => self::REFEREE,
            TagTeam::class => self::TAG_TEAM,
            Title::class => self::TITLE,
            Stable::class => self::STABLE,
            default => throw new InvalidArgumentException(
                'Unsupported roster member type: '.get_class($model)
            )
        };
    }

    /**
     * Get a human-readable label for this roster member type.
     *
     * @return string The display label
     *
     * @example
     * ```php
     * RosterMemberType::WRESTLER->getLabel(); // 'Wrestler'
     * RosterMemberType::TAG_TEAM->getLabel(); // 'Tag Team'
     * ```
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::WRESTLER => 'Wrestler',
            self::MANAGER => 'Manager',
            self::REFEREE => 'Referee',
            self::TAG_TEAM => 'Tag Team',
            self::TITLE => 'Title',
            self::STABLE => 'Stable',
        };
    }

    /**
     * Get the plural form of the roster member type label.
     *
     * @return string The plural display label
     *
     * @example
     * ```php
     * RosterMemberType::WRESTLER->getPluralLabel(); // 'Wrestlers'
     * RosterMemberType::TAG_TEAM->getPluralLabel(); // 'Tag Teams'
     * ```
     */
    public function getPluralLabel(): string
    {
        return match ($this) {
            self::WRESTLER => 'Wrestlers',
            self::MANAGER => 'Managers',
            self::REFEREE => 'Referees',
            self::TAG_TEAM => 'Tag Teams',
            self::TITLE => 'Titles',
            self::STABLE => 'Stables',
        };
    }

    /**
     * Determine if this roster member type can be injured.
     *
     * Only individual people (Wrestlers, Managers, Referees) can be injured.
     * Teams, groups, and championships cannot be injured as business entities.
     *
     * @return bool True if this type can be injured, false otherwise
     *
     * @example
     * ```php
     * RosterMemberType::WRESTLER->canBeInjured(); // true
     * RosterMemberType::TAG_TEAM->canBeInjured(); // false
     * ```
     */
    public function canBeInjured(): bool
    {
        return match ($this) {
            self::WRESTLER, self::MANAGER, self::REFEREE => true,
            self::TAG_TEAM, self::TITLE, self::STABLE => false,
        };
    }

    /**
     * Determine if this roster member type can be suspended.
     *
     * Most roster members can be suspended except for pure individuals
     * in some business contexts. Tag teams and titles can be suspended as units.
     *
     * @return bool True if this type can be suspended, false otherwise
     *
     * @example
     * ```php
     * RosterMemberType::WRESTLER->canBeSuspended(); // true
     * RosterMemberType::TAG_TEAM->canBeSuspended(); // true
     * ```
     */
    public function canBeSuspended(): bool
    {
        return match ($this) {
            self::WRESTLER, self::MANAGER, self::REFEREE, self::TAG_TEAM, self::TITLE, self::STABLE => true,
        };
    }

    /**
     * Determine if this roster member type can be employed.
     *
     * All roster member types can have employment relationships
     * with the wrestling promotion.
     *
     * @return bool True if this type can be employed, false otherwise
     *
     * @example
     * ```php
     * RosterMemberType::WRESTLER->canBeEmployed(); // true
     * RosterMemberType::TAG_TEAM->canBeEmployed(); // true
     * ```
     */
    public function canBeEmployed(): bool
    {
        return match ($this) {
            self::WRESTLER, self::MANAGER, self::REFEREE, self::TAG_TEAM, self::TITLE, self::STABLE => true,
        };
    }

    /**
     * Determine if this roster member type can be retired.
     *
     * All roster member types can be retired from active competition
     * or promotional activities.
     *
     * @return bool True if this type can be retired, false otherwise
     *
     * @example
     * ```php
     * RosterMemberType::WRESTLER->canBeRetired(); // true
     * RosterMemberType::REFEREE->canBeRetired(); // true
     * ```
     */
    public function canBeRetired(): bool
    {
        return match ($this) {
            self::WRESTLER, self::MANAGER, self::REFEREE, self::TAG_TEAM, self::TITLE, self::STABLE => true,
        };
    }

    /**
     * Determine if this roster member type represents an individual person.
     *
     * Individual roster members are single people as opposed to groups or teams.
     * This affects injury capabilities and validation strategies.
     *
     * @return bool True if this type represents an individual, false otherwise
     *
     * @example
     * ```php
     * RosterMemberType::WRESTLER->isIndividual(); // true
     * RosterMemberType::TAG_TEAM->isIndividual(); // false
     * ```
     */
    public function isIndividual(): bool
    {
        return match ($this) {
            self::WRESTLER, self::MANAGER, self::REFEREE => true,
            self::TAG_TEAM, self::TITLE, self::STABLE => false,
        };
    }

    /**
     * Determine if this roster member type represents a team or group.
     *
     * Team roster members are composed of multiple individuals and have
     * different business rules and validation strategies.
     *
     * @return bool True if this type represents a team, false otherwise
     *
     * @example
     * ```php
     * RosterMemberType::TAG_TEAM->isTeam(); // true
     * RosterMemberType::WRESTLER->isTeam(); // false
     * ```
     */
    public function isTeam(): bool
    {
        return match ($this) {
            self::TAG_TEAM, self::TITLE, self::STABLE => true,
            self::WRESTLER, self::MANAGER, self::REFEREE => false,
        };
    }

    /**
     * Get the model class name for this roster member type.
     *
     * Returns the fully qualified class name for the Eloquent model
     * that represents this roster member type.
     *
     * @return class-string<Model> The model class name
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * RosterMemberType::WRESTLER->getModelClass(); // Wrestler::class
     * RosterMemberType::TAG_TEAM->getModelClass(); // TagTeam::class
     * ```
     */
    public function getModelClass(): string
    {
        return match ($this) {
            self::WRESTLER => Wrestler::class,
            self::MANAGER => Manager::class,
            self::REFEREE => Referee::class,
            self::TAG_TEAM => TagTeam::class,
            self::TITLE => Title::class,
            self::STABLE => Stable::class,
        };
    }

    /**
     * Get the appropriate validation strategy for suspension operations.
     *
     * Returns the class name for the validation strategy that should be used
     * when validating suspension operations for this roster member type.
     *
     * @return class-string The validation strategy class name
     *
     * @example
     * ```php
     * $strategy = RosterMemberType::WRESTLER->getSuspensionValidationStrategy();
     * // Returns: IndividualSuspensionValidation::class
     *
     * $strategy = RosterMemberType::TAG_TEAM->getSuspensionValidationStrategy();
     * // Returns: TagTeamSuspensionValidation::class
     * ```
     */
    public function getSuspensionValidationStrategy(): string
    {
        return match ($this) {
            self::TAG_TEAM, self::TITLE => 'App\Models\Validation\Strategies\TagTeamSuspensionValidation',
            self::STABLE => 'App\Models\Validation\Strategies\StableSuspensionValidation',
            self::WRESTLER, self::MANAGER, self::REFEREE => 'App\Models\Validation\Strategies\IndividualSuspensionValidation',
        };
    }

    /**
     * Get the appropriate validation strategy for retirement operations.
     *
     * Returns the class name for the validation strategy that should be used
     * when validating retirement operations for this roster member type.
     *
     * @return class-string The validation strategy class name
     *
     * @example
     * ```php
     * $strategy = RosterMemberType::WRESTLER->getRetirementValidationStrategy();
     * // Returns: IndividualRetirementValidation::class
     *
     * $strategy = RosterMemberType::TAG_TEAM->getRetirementValidationStrategy();
     * // Returns: TagTeamRetirementValidation::class
     * ```
     */
    public function getRetirementValidationStrategy(): string
    {
        return match ($this) {
            self::TAG_TEAM => 'App\Models\Validation\Strategies\TagTeamRetirementValidation',
            self::TITLE => 'App\Models\Validation\Strategies\TitleRetirementValidation',
            self::STABLE => 'App\Models\Validation\Strategies\StableRetirementValidation',
            self::WRESTLER, self::MANAGER, self::REFEREE => 'App\Models\Validation\Strategies\IndividualRetirementValidation',
        };
    }

    /**
     * Get all roster member types that are individuals.
     *
     * @return array<self> Array of individual roster member types
     *
     * @example
     * ```php
     * $individuals = RosterMemberType::individuals();
     * // Returns: [WRESTLER, MANAGER, REFEREE]
     * ```
     */
    public static function individuals(): array
    {
        return [self::WRESTLER, self::MANAGER, self::REFEREE];
    }

    /**
     * Get all roster member types that are teams.
     *
     * @return array<self> Array of team roster member types
     *
     * @example
     * ```php
     * $teams = RosterMemberType::teams();
     * // Returns: [TAG_TEAM, TITLE]
     * ```
     */
    public static function teams(): array
    {
        return [self::TAG_TEAM, self::TITLE];
    }

    /**
     * Get all roster member types that can be injured.
     *
     * @return array<self> Array of injurable roster member types
     *
     * @example
     * ```php
     * $injurable = RosterMemberType::injurable();
     * // Returns: [WRESTLER, MANAGER, REFEREE]
     * ```
     */
    public static function injurable(): array
    {
        return array_filter(
            self::cases(),
            fn (self $type) => $type->canBeInjured()
        );
    }

    /**
     * Get all roster member types that can be suspended.
     *
     * @return array<self> Array of suspendable roster member types
     *
     * @example
     * ```php
     * $suspendable = RosterMemberType::suspendable();
     * // Returns: [WRESTLER, MANAGER, REFEREE, TAG_TEAM, TITLE]
     * ```
     */
    public static function suspendable(): array
    {
        return array_filter(
            self::cases(),
            fn (self $type) => $type->canBeSuspended()
        );
    }

    /**
     * Check if a model has a specific capability using type-safe enum lookup.
     *
     * This method replaces method_exists() checks with enum-based capability checking,
     * providing better type safety and eliminating runtime method existence checks.
     *
     * @param  Model  $model  The model to check capability for
     * @param  string  $capability  The capability to check ('injured', 'suspended', 'employed', 'retired')
     * @return bool True if the model type supports the capability, false otherwise
     *
     * @example
     * ```php
     * // Instead of: method_exists($model, 'isInjured')
     * if (RosterMemberType::hasCapability($wrestler, 'injured')) {
     *     // Check if wrestler is injured
     * }
     *
     * // Instead of: method_exists($model, 'isSuspended')
     * if (RosterMemberType::hasCapability($tagTeam, 'suspended')) {
     *     // Check if tag team is suspended
     * }
     * ```
     */
    public static function hasCapability(Model $model, string $capability): bool
    {
        $type = self::fromModel($model);

        return match ($capability) {
            'injured' => $type->canBeInjured(),
            'suspended' => $type->canBeSuspended(),
            'employed' => $type->canBeEmployed(),
            'retired' => $type->canBeRetired(),
            default => false,
        };
    }

    /**
     * Get validation strategy class for a specific operation type.
     *
     * This method centralizes strategy selection logic and can be extended
     * for additional operation types beyond suspension and retirement.
     *
     * @param  Model  $model  The model to get strategy for
     * @param  string  $operation  The operation type ('suspension', 'retirement')
     * @return class-string The validation strategy class name
     *
     * @throws InvalidArgumentException If the operation type is not supported
     *
     * @example
     * ```php
     * $strategy = RosterMemberType::getValidationStrategy($wrestler, 'suspension');
     * // Returns: IndividualSuspensionValidation::class
     *
     * $strategy = RosterMemberType::getValidationStrategy($tagTeam, 'retirement');
     * // Returns: TagTeamRetirementValidation::class
     * ```
     */
    public static function getValidationStrategy(Model $model, string $operation): string
    {
        $type = self::fromModel($model);

        return match ($operation) {
            'suspension' => $type->getSuspensionValidationStrategy(),
            'retirement' => $type->getRetirementValidationStrategy(),
            default => throw new InvalidArgumentException("Unsupported operation type: {$operation}"),
        };
    }
}
