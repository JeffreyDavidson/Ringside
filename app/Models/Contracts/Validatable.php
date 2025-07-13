<?php

declare(strict_types=1);

namespace App\Models\Contracts;

/**
 * Contract for models that can be validated for various business operations.
 *
 * This interface defines methods for checking whether an entity can undergo
 * specific business operations like employment, retirement, suspension, or injury.
 * It provides a standard way to validate business rules across different model types.
 *
 * Models implementing this interface should provide concrete implementations
 * that check the specific business rules for their entity type.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model The model that can be validated
 *
 * @example
 * ```php
 * class Wrestler extends Model implements Validatable
 * {
 *     public function canBeEmployed(): bool
 *     {
 *         return !$this->isRetired() && !$this->isEmployed();
 *     }
 *
 *     public function canBeRetired(): bool
 *     {
 *         return $this->isEmployed() || $this->hasEmploymentHistory();
 *     }
 * }
 * ```
 */
interface Validatable
{
    /**
     * Determine if the entity can be employed.
     *
     * This method should check all business rules that determine whether
     * an entity is eligible for employment, such as retirement status,
     * current employment status, and any other relevant constraints.
     *
     * @return bool True if the entity can be employed, false otherwise
     *
     * @example
     * ```php
     * if ($wrestler->canBeEmployed()) {
     *     EmployAction::run($wrestler, $startDate);
     * }
     * ```
     */
    public function canBeEmployed(): bool;

    /**
     * Determine if the entity can be retired.
     *
     * This method should check all business rules that determine whether
     * an entity is eligible for retirement, such as current employment status,
     * active championships, or ongoing commitments.
     *
     * @return bool True if the entity can be retired, false otherwise
     *
     * @example
     * ```php
     * if ($wrestler->canBeRetired()) {
     *     RetireAction::run($wrestler, $retirementDate);
     * }
     * ```
     */
    public function canBeRetired(): bool;

    /**
     * Determine if the entity can be suspended.
     *
     * This method should check all business rules that determine whether
     * an entity is eligible for suspension, such as current employment status,
     * existing suspension status, and entity-specific constraints.
     *
     * @return bool True if the entity can be suspended, false otherwise
     *
     * @example
     * ```php
     * if ($wrestler->canBeSuspended()) {
     *     SuspendAction::run($wrestler, $suspensionDate);
     * }
     * ```
     */
    public function canBeSuspended(): bool;

    /**
     * Determine if the entity can be injured.
     *
     * This method should check all business rules that determine whether
     * an entity is eligible for injury status. Note that only individual
     * entities (Wrestlers, Managers, Referees) can be injured.
     *
     * @return bool True if the entity can be injured, false otherwise
     *
     * @example
     * ```php
     * if ($wrestler->canBeInjured()) {
     *     InjureAction::run($wrestler, $injuryDate);
     * }
     * ```
     */
    public function canBeInjured(): bool;

    /**
     * Determine if the entity can be released from employment.
     *
     * This method should check all business rules that determine whether
     * an entity is eligible for release, such as current employment status
     * and any ongoing commitments.
     *
     * @return bool True if the entity can be released, false otherwise
     *
     * @example
     * ```php
     * if ($wrestler->canBeReleased()) {
     *     ReleaseAction::run($wrestler, $releaseDate);
     * }
     * ```
     */
    public function canBeReleased(): bool;

    /**
     * Determine if the entity can be reinstated from suspension.
     *
     * This method should check all business rules that determine whether
     * an entity is eligible for reinstatement, primarily checking if
     * they are currently suspended.
     *
     * @return bool True if the entity can be reinstated, false otherwise
     *
     * @example
     * ```php
     * if ($wrestler->canBeReinstated()) {
     *     ReinstateAction::run($wrestler, $reinstatementDate);
     * }
     * ```
     */
    public function canBeReinstated(): bool;
}
