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

    /** @return array<string, string> */
    public static function filterOptions(): array
    {
        /** @var array<string, string> $statusOptions */
        $statusOptions = array_combine(
            array_map(static fn (self $status): string => $status->value, self::cases()),
            array_map(static fn (self $status): string => $status->label(), self::cases()),
        );

        return [
            '' => __('core.all'),
            ...$statusOptions,
        ];
    }
}
