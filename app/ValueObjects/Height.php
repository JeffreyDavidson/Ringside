<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Value object representing a wrestler's height in feet and inches.
 *
 * This immutable value object encapsulates height measurements specifically
 * for wrestlers, providing validation, formatting, and conversion methods.
 * Height is stored as feet and inches since this is the standard format
 * used in professional wrestling.
 *
 * @example
 * ```php
 * // Create a height for a 6'2" wrestler
 * $height = new Height(6, 2);
 * echo $height; // "6'2""
 * echo $height->toInches(); // 74
 * ```
 */
readonly class Height
{
    /**
     * Create a new Height instance.
     *
     * @param  int  $feet  The feet component of the height (0-8 typical range for wrestlers)
     * @param  int  $inches  The inches component of the height (0-11)
     */
    public function __construct(
        public int $feet,
        public int $inches
    ) {}

    /**
     * Get the height formatted as a string in wrestling notation.
     *
     * Returns the height in the standard wrestling format: feet'inches"
     *
     * @return string The formatted height (e.g., "6'2"")
     *
     * @example
     * ```php
     * $height = new Height(6, 2);
     * echo $height->__toString(); // "6'2""
     * echo (string) $height;      // "6'2""
     * ```
     */
    public function __toString(): string
    {
        return "{$this->feet}'{$this->inches}\"";
    }

    /**
     * Convert the height to total inches.
     *
     * Converts the feet and inches to a total number of inches,
     * useful for height comparisons and calculations.
     *
     * @return int The total height in inches
     *
     * @example
     * ```php
     * $height = new Height(6, 2);
     * echo $height->toInches(); // 74 (6*12 + 2)
     * ```
     */
    public function toInches(): int
    {
        return $this->feet * 12 + $this->inches;
    }
}
