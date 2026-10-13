<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum LiveShowStatus: string
{
    case Prepared = 'prepared';
    case Running = 'running';
    case Completed = 'completed';
}
