<?php

declare(strict_types=1);

namespace App\Exceptions\Lifecycle;

use App\Exceptions\BaseBusinessException;
use Illuminate\Support\Carbon;

final class InvalidDateRangeException extends BaseBusinessException
{
    public static function endBeforeStart(Carbon $startDate, Carbon $endDate, ?string $context = null): static
    {
        $replacements = [
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
        ];

        if ($context) {
            return new self(__('core.errors.date_range.end_before_start_for_context', [...$replacements, 'context' => $context]));
        }

        return new self(__('core.errors.date_range.end_before_start', $replacements));
    }

    public static function futureNotAllowed(Carbon $date, string $context): static
    {
        return new self(__('core.errors.date_range.future_not_allowed', [
            'context' => $context,
            'date' => $date->format('Y-m-d'),
        ]));
    }
}
