<?php

declare(strict_types=1);

namespace App\Enums\Events;

use App\Enums\Concerns\ProvidesFilterOptions;
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
        return __("events.status.{$this->value}");
    }
}
