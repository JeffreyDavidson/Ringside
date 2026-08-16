<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Helper class for common date operations.
 */
class DateHelper
{
    /**
     * Resolve a date by using the provided date or defaulting to now.
     *
     * This helper eliminates the repetitive `$date ?? now()` pattern
     * throughout the codebase and provides a consistent way to handle
     * optional date parameters.
     *
     * @param  Carbon|null  $date  The optional date to use
     * @return Carbon The resolved date (provided date or current timestamp)
     *
     */
    public static function resolveDate(?Carbon $date): Carbon
    {
        return $date ?? now();
    }
}
