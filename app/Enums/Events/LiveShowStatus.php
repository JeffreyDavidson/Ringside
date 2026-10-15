<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum LiveShowStatus: string
{
    case Prepared = 'prepared';
    case Running = 'running';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Prepared => __('live-show.prepared'),
            self::Running => __('live-show.running'),
            self::Completed => __('live-show.completed_heading'),
        };
    }
}
