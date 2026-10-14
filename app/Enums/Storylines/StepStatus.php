<?php

declare(strict_types=1);

namespace App\Enums\Storylines;

enum StepStatus: string
{
    case Planned = 'planned';
    case Postponed = 'postponed';
    case Cancelled = 'cancelled';
    case Happened = 'happened';
}
