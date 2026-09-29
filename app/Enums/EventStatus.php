<?php

declare(strict_types=1);

namespace App\Enums;

use App\Support\Enums\ProvidesFilterOptions;
use Carbon\CarbonInterface;

enum EventStatus: string
{
    use ProvidesFilterOptions;

    case Past = 'past';
    case Scheduled = 'scheduled';
    case Unscheduled = 'unscheduled';

    public static function fromDate(?CarbonInterface $date): self
    {
        if (! $date instanceof CarbonInterface) {
            return self::Unscheduled;
        }

        return $date->isPast() ? self::Past : self::Scheduled;
    }

    public function label(): string
    {
        return match ($this) {
            self::Past => 'Past',
            self::Scheduled => 'Scheduled',
            self::Unscheduled => 'Unscheduled',
        };
    }
}
