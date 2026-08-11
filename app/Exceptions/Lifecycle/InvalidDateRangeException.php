<?php

declare(strict_types=1);

namespace App\Exceptions\Lifecycle;

use App\Exceptions\BaseBusinessException;
use Illuminate\Support\Carbon;

final class InvalidDateRangeException extends BaseBusinessException
{
    public static function endBeforeStart(Carbon $startDate, Carbon $endDate, ?string $context = null): static
    {
        $contextInformation = $context ? " for {$context}" : '';

        return new self(
            "Invalid date range{$contextInformation}: end date ({$endDate->format('Y-m-d')}) cannot be before start date ({$startDate->format('Y-m-d')}). Ensure logical date ordering."
        );
    }

    public static function futureNotAllowed(Carbon $date, string $context): static
    {
        return new self(
            "{$context} date ({$date->format('Y-m-d')}) cannot be in the future. Use current or past date only."
        );
    }
}
