<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing a wrestler's height in feet and inches.
 *
 * This immutable value object encapsulates height measurements specifically
 * for wrestlers, providing validation, formatting, and conversion methods.
 * Height is stored as feet and inches since this is the standard format
 * used in professional wrestling.
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
    ) {
        if ($feet < 0) {
            throw new InvalidArgumentException('Height feet cannot be negative.');
        }

        if ($inches < 0 || $inches > 11) {
            throw new InvalidArgumentException('Height inches must be between 0 and 11.');
        }

        if ($feet === 0 && $inches === 0) {
            throw new InvalidArgumentException('Height must be greater than zero.');
        }
    }

    public static function fromInches(int $inches): self
    {
        if ($inches <= 0) {
            throw new InvalidArgumentException('Height must be greater than zero.');
        }

        return new self(intdiv($inches, 12), $inches % 12);
    }

    /**
     * Get the height formatted as a string in wrestling notation.
     *
     * Returns the height in the standard wrestling format: feet'inches"
     *
     * @return string The formatted height (e.g., "6'2"")
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
     */
    public function toInches(): int
    {
        return $this->feet * 12 + $this->inches;
    }
}
