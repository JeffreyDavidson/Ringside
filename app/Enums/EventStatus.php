<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;

enum EventStatus: string
{
    case Past = 'past';
    case Scheduled = 'scheduled';
    case Unscheduled = 'unscheduled';

    public static function fromDate(?CarbonInterface $date): self
    {
        if ($date === null) {
            return self::Unscheduled;
        }

        return $date->isPast() ? self::Past : self::Scheduled;
    }

    public function color(): string
    {
        return match ($this) {
            self::Past => 'dark',
            self::Scheduled => 'success',
            self::Unscheduled => 'danger',
        };
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
