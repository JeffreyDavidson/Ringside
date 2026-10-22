<?php

declare(strict_types=1);

namespace App\Exceptions\Lifecycle;

use App\Enums\Lifecycle\DateRangeContext;
use App\Exceptions\BaseBusinessException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class InvalidDateRangeException extends BaseBusinessException
{
    public static function endBeforeStart(Carbon $startDate, Carbon $endDate, ?DateRangeContext $context = null): static
    {
        $replacements = [
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
        ];

        if ($context instanceof DateRangeContext) {
            return new self(__('core.errors.date_range.end_before_start_for_context', [...$replacements, 'context' => $context->label()]));
        }

        return new self(__('core.errors.date_range.end_before_start', $replacements));
    }

    public static function futureNotAllowed(Carbon $date, DateRangeContext $context): static
    {
        return new self(__('core.errors.date_range.future_not_allowed', [
            'context' => Str::ucfirst($context->label()),
            'date' => $date->format('Y-m-d'),
        ]));
    }
}
