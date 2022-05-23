<?php

declare(strict_types=1);

namespace App\Enums;

enum EventStatus: string
{
    case PAST = 'past';
    case SCHEDULED = 'scheduled';
    case UNSCHEDULED = 'unscheduled';

    public function color(): string
    {
        return match ($this) {
            self::PAST => 'dark',
            self::SCHEDULED => 'success',
            self::UNSCHEDULED => 'danger',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::PAST => 'Past',
            self::SCHEDULED => 'Scheduled',
            self::UNSCHEDULED => 'Unscheduled',
        };
    }
}
