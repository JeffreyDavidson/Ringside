<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum LiveItemStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Finished = 'finished';
    case Skipped = 'skipped';

    public function label(): string
    {
        return __("live-show.{$this->value}");
    }
}
