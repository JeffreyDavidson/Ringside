<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum SponsorInstructionMode: string
{
    case Segment = 'segment';
    case Setup = 'setup';
    case Remove = 'remove';
}
