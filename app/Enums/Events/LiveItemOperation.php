<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum LiveItemOperation: string
{
    case Start = 'start';
    case Finish = 'finish';
    case Skip = 'skip';
    case Correct = 'correct';
    case Reopen = 'reopen';
}
