<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * Provides generic status checking functionality for any enum-based status field.
 *
 * This trait offers utility methods for checking enum-based status fields on models.
 * It's designed to work with any BackedEnum and any field name, making it highly
 * reusable across different model types and status systems.
 *
 * @template TStatus of \BackedEnum The enum type for the status field
 *
 * @example
 * ```php
 * class Wrestler extends Model
 * {
 *     use HasEnumStatus;
 *
 *     protected $casts = [
 *         'status' => EmploymentStatus::class,
 *         'health_status' => HealthStatus::class,
 *     ];
 *
 *     public function isEmployed(): bool
 *     {
 *         return $this->hasStatus(EmploymentStatus::Employed);
 *     }
 *
 *     public function isHealthy(): bool
 *     {
 *         return $this->hasStatus(HealthStatus::Healthy, 'health_status');
 *     }
 * }
 * ```
 */
trait HasEnumStatus
{
    /**
     * Check if the model has a specific status.
     *
     * This method checks if the specified field on the model matches the given
     * enum value. It safely handles cases where the field might not be set.
     *
     * @param  TStatus  $status  The status enum value to check for
     * @param  string  $field  The status field name (defaults to 'status')
     * @return bool True if the model has the specified status, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * // Check default 'status' field
     * if ($wrestler->hasStatus(EmploymentStatus::Employed)) {
     *     echo "Wrestler is employed";
     * }
     *
     * // Check custom field
     * if ($wrestler->hasStatus(HealthStatus::Injured, 'health_status')) {
     *     echo "Wrestler is injured";
     * }
     * ```
     */
    public function hasStatus($status, string $field = 'status'): bool
    {
        return isset($this->{$field}) && $this->{$field} === $status;
    }

    /**
     * Check if the model has any of the specified statuses.
     *
     * This method is useful when you need to check if a model has one of several
     * possible status values. It performs strict comparison using in_array with
     * the strict flag enabled.
     *
     * @param  array<TStatus>  $statuses  Array of status enum values to check for
     * @param  string  $field  The status field name (defaults to 'status')
     * @return bool True if the model has any of the specified statuses, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * // Check if wrestler is unavailable (unemployed or released)
     * if ($wrestler->hasAnyStatus([
     *     EmploymentStatus::Unemployed,
     *     EmploymentStatus::Released
     * ])) {
     *     echo "Wrestler is not available for booking";
     * }
     *
     * // Check custom field for multiple health issues
     * if ($wrestler->hasAnyStatus([
     *     HealthStatus::Injured,
     *     HealthStatus::Sick,
     *     HealthStatus::Suspended
     * ], 'health_status')) {
     *     echo "Wrestler has health concerns";
     * }
     * ```
     */
    public function hasAnyStatus(array $statuses, string $field = 'status'): bool
    {
        if (! isset($this->{$field})) {
            return false;
        }

        return in_array($this->{$field}, $statuses, true);
    }

    /**
     * Check if the model does not have a specific status.
     *
     * This is a convenience method that's equivalent to !hasStatus() but can
     * make code more readable in certain contexts.
     *
     * @param  TStatus  $status  The status enum value to check against
     * @param  string  $field  The status field name (defaults to 'status')
     * @return bool True if the model does not have the specified status, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->doesNotHaveStatus(EmploymentStatus::Retired)) {
     *     echo "Wrestler is not retired";
     * }
     * ```
     */
    public function doesNotHaveStatus($status, string $field = 'status'): bool
    {
        return ! $this->hasStatus($status, $field);
    }

    /**
     * Check if the model has none of the specified statuses.
     *
     * This method is useful when you need to ensure a model doesn't have any
     * of several problematic status values.
     *
     * @param  array<TStatus>  $statuses  Array of status enum values to check against
     * @param  string  $field  The status field name (defaults to 'status')
     * @return bool True if the model has none of the specified statuses, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * // Check if wrestler is available (not in any problematic state)
     * if ($wrestler->hasNoneOfStatuses([
     *     EmploymentStatus::Unemployed,
     *     EmploymentStatus::Released,
     *     EmploymentStatus::Suspended
     * ])) {
     *     echo "Wrestler is available for booking";
     * }
     * ```
     */
    public function hasNoneOfStatuses(array $statuses, string $field = 'status'): bool
    {
        return ! $this->hasAnyStatus($statuses, $field);
    }
}
