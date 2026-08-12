<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use LogicException;

/**
 * Provides generic model class resolution functionality for Eloquent model relationships.
 *
 * This trait centralizes the logic for automatically resolving related model class names
 * based on naming conventions. It's particularly useful for status-related traits that
 * need to resolve employment, retirement, suspension, injury, and other related models.
 *
 * DESIGN PRINCIPLES:
 * - Consistent model resolution across all relationship types
 * - Flexible suffix-based naming for different relationship types
 * - Explicit protected resolver hooks for specialized models and tests
 * - Clear error handling for missing model classes
 *
 * NAMING CONVENTION:
 * Given a parent model like 'App\Models\Wrestlers\Wrestler',
 * and a suffix like 'Retirement', resolves to:
 * 'App\Models\Wrestlers\WrestlerRetirement'
 *
 * @example
 * ```php
 * trait IsEmployable
 * {
 *     use ResolvesRelatedModels;
 *
 *     protected function resolveRetirementModelClass(): string
 *     {
 *         return $this->resolveRelatedModelClass('Retirement');
 *     }
 * }
 * ```
 */
trait ResolvesRelatedModels
{
    /**
     * Resolve a related model class based on suffix.
     *
     * Automatically determines the related model class name using naming conventions.
     * For example, if the parent model is 'Wrestler' and suffix is 'Retirement',
     * it will resolve to 'WrestlerRetirement' in the same namespace.
     *
     * @param  string  $suffix  The suffix to append to the base model name (e.g., 'Retirement', 'Suspension')
     * @throws LogicException If the resolved model class doesn't exist
     * @return string The fully qualified class name of the resolved model
     *
     * @example
     * ```php
     * // In IsRetirable trait:
     * $retirementClass = $this->resolveRelatedModelClass('Retirement');
     * // Returns: 'App\Models\Wrestlers\WrestlerRetirement'
     * // Returns: 'App\Models\Wrestlers\WrestlerRetirement'
     * ```
     */
    protected function resolveRelatedModelClass(string $suffix): string
    {
        return $this->performModelResolution($suffix);
    }

    /**
     * Perform the actual model class resolution.
     *
     * @param  string  $suffix  The suffix to append to the base model name
     * @throws LogicException If the resolved model class doesn't exist
     * @return string The fully qualified class name of the resolved model
     */
    private function performModelResolution(string $suffix): string
    {
        $declaringClass = static::class;

        // Handle Mockery mock classes - extract the original class name
        if (str_starts_with($declaringClass, 'Mockery_')) {
            // Extract original class from Mockery mock class name
            $parts = explode('_', $declaringClass);
            if (count($parts) >= 3) {
                // Reconstruct the original class name from Mockery_#_Original_Class_Name format
                $declaringClass = implode('\\', array_slice($parts, 2));
            }
        }

        $baseModelName = class_basename($declaringClass);

        // Build the related model class name by replacing only the class name, not the namespace
        $relatedModelName = $baseModelName.$suffix;
        $lastBackslashPos = mb_strrpos($declaringClass, '\\');
        $namespace = $lastBackslashPos !== false ? mb_substr($declaringClass, 0, $lastBackslashPos) : '';
        $resolvedClass = $namespace ? $namespace.'\\'.$relatedModelName : $relatedModelName;

        // Validate that the resolved class exists
        if (! class_exists($resolvedClass)) {
            throw new LogicException(
                "Related model [{$resolvedClass}] not found for [{$declaringClass}] with suffix [{$suffix}]. ".
                'Ensure the class exists or override the domain-specific resolver method.'
            );
        }

        return $resolvedClass;
    }

    /**
     * Check if a related model class exists for the given suffix.
     *
     * @param  string  $suffix  The suffix to check
     * @return bool True if the related model class exists, false otherwise
     */
    protected function relatedModelExists(string $suffix): bool
    {
        try {
            $this->resolveRelatedModelClass($suffix);

            return true;
        } catch (LogicException) {
            return false;
        }
    }
}
