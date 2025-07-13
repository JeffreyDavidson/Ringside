<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use RuntimeException;

/**
 * Provides generic model class resolution functionality for Eloquent model relationships.
 *
 * This trait centralizes the logic for automatically resolving related model class names
 * based on naming conventions. It's particularly useful for status-related traits that
 * need to resolve employment, retirement, suspension, injury, and other related models.
 *
 * DESIGN PRINCIPLES:
 * - Consistent model resolution across all relationship types
 * - Efficient static caching to avoid repeated class resolution
 * - Flexible suffix-based naming for different relationship types
 * - Testing support through fake model override mechanisms
 * - Clear error handling for missing model classes
 *
 * NAMING CONVENTION:
 * Given a parent model like 'App\Models\Wrestlers\Wrestler',
 * and a suffix like 'Employment', resolves to:
 * 'App\Models\Wrestlers\WrestlerEmployment'
 *
 * CACHING STRATEGY:
 * Uses static properties to cache resolved model classes per suffix type,
 * improving performance for repeated resolutions within the same request.
 *
 * @example
 * ```php
 * trait IsEmployable
 * {
 *     use ResolvesRelatedModels;
 *
 *     protected function resolveEmploymentModelClass(): string
 *     {
 *         return $this->resolveRelatedModelClass('Employment');
 *     }
 *
 *     public static function fakeEmploymentModel(string $class): void
 *     {
 *         self::cacheRelatedModel('Employment', $class);
 *     }
 * }
 * ```
 */
trait ResolvesRelatedModels
{
    /**
     * Cache for resolved model classes by suffix.
     *
     * Stores resolved model class names to avoid repeated resolution.
     * Format: ['Employment' => 'App\Models\Wrestlers\WrestlerEmployment', ...]
     *
     * @var array<string, string>
     */
    protected static array $resolvedRelatedModels = [];

    /**
     * Resolve a related model class based on suffix.
     *
     * Automatically determines the related model class name using naming conventions.
     * For example, if the parent model is 'Wrestler' and suffix is 'Employment',
     * it will resolve to 'WrestlerEmployment' in the same namespace.
     *
     * @param  string  $suffix  The suffix to append to the base model name (e.g., 'Employment', 'Retirement')
     * @return string The fully qualified class name of the resolved model
     *
     * @throws RuntimeException If the resolved model class doesn't exist
     *
     * @example
     * ```php
     * // In IsEmployable trait:
     * $employmentClass = $this->resolveRelatedModelClass('Employment');
     * // Returns: 'App\Models\Wrestlers\WrestlerEmployment'
     *
     * // In IsRetirable trait:
     * $retirementClass = $this->resolveRelatedModelClass('Retirement');
     * // Returns: 'App\Models\Wrestlers\WrestlerRetirement'
     * ```
     */
    protected function resolveRelatedModelClass(string $suffix): string
    {
        // Check if already cached for this suffix
        $cacheKey = $this->buildCacheKey($suffix);
        if (isset(static::$resolvedRelatedModels[$cacheKey])) {
            return static::$resolvedRelatedModels[$cacheKey];
        }

        // Resolve and cache the model class
        $resolvedClass = $this->performModelResolution($suffix);
        static::$resolvedRelatedModels[$cacheKey] = $resolvedClass;

        return $resolvedClass;
    }

    /**
     * Cache a related model class for testing or customization.
     *
     * Allows overriding the automatic model class resolution, which is particularly
     * useful for testing scenarios where you might want to use a different model
     * class or mock.
     *
     * @param  string  $suffix  The suffix type (e.g., 'Employment', 'Retirement')
     * @param  string  $class  The fully qualified class name to cache
     *
     * @example
     * ```php
     * // In a test:
     * Wrestler::cacheRelatedModel('Employment', MockWrestlerEmployment::class);
     *
     * // Or for customization:
     * Wrestler::cacheRelatedModel('Employment', CustomEmploymentModel::class);
     * ```
     */
    protected static function cacheRelatedModel(string $suffix, string $class): void
    {
        $cacheKey = static::class.'::'.$suffix;
        static::$resolvedRelatedModels[$cacheKey] = $class;
    }

    /**
     * Get the cached model class for a suffix, if it exists.
     *
     * @param  string  $suffix  The suffix to check for
     * @return string|null The cached class name, or null if not cached
     */
    protected function getCachedRelatedModel(string $suffix): ?string
    {
        $cacheKey = $this->buildCacheKey($suffix);

        return static::$resolvedRelatedModels[$cacheKey] ?? null;
    }

    /**
     * Clear the cache for a specific suffix or all cached models.
     *
     * Useful for testing scenarios where you need to reset the resolution cache.
     *
     * @param  string|null  $suffix  The specific suffix to clear, or null to clear all
     */
    protected static function clearRelatedModelCache(?string $suffix = null): void
    {
        if ($suffix === null) {
            static::$resolvedRelatedModels = [];
        } else {
            $cacheKey = static::class.'::'.$suffix;
            unset(static::$resolvedRelatedModels[$cacheKey]);
        }
    }

    /**
     * Build a cache key for the given suffix.
     *
     * @param  string  $suffix  The suffix to build a key for
     * @return string The cache key
     */
    private function buildCacheKey(string $suffix): string
    {
        return static::class.'::'.$suffix;
    }

    /**
     * Perform the actual model class resolution.
     *
     * @param  string  $suffix  The suffix to append to the base model name
     * @return string The fully qualified class name of the resolved model
     *
     * @throws RuntimeException If the resolved model class doesn't exist
     */
    private function performModelResolution(string $suffix): string
    {
        $declaringClass = static::class;
        $baseModelName = class_basename($declaringClass);

        // Build the related model class name by replacing only the class name, not the namespace
        $relatedModelName = $baseModelName.$suffix;
        $namespace = mb_substr($declaringClass, 0, mb_strrpos($declaringClass, '\\'));
        $resolvedClass = $namespace.'\\'.$relatedModelName;

        // Validate that the resolved class exists
        if (! class_exists($resolvedClass)) {
            throw new RuntimeException(
                "Related model [{$resolvedClass}] not found for [{$declaringClass}] with suffix [{$suffix}]. ".
                'Ensure the class exists or override the resolution using cacheRelatedModel().'
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
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * Get all cached model classes for debugging purposes.
     *
     * @return array<string, string> Array of cached model classes
     */
    protected static function getAllCachedModels(): array
    {
        return static::$resolvedRelatedModels;
    }
}
