<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum LiveItemStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Finished = 'finished';
    case Skipped = 'skipped';
}
