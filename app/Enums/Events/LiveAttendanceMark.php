<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum LiveAttendanceMark: string
{
    case Arrived = 'arrived';
    case Briefed = 'briefed';
}
