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

    public function label(): string
    {
        return __("live-show.{$this->value}");
    }

    /** Skipping, correcting and reopening change the record of what happened, so each needs a reason. */
    public function requiresReason(): bool
    {
        return in_array($this, [self::Skip, self::Correct, self::Reopen], true);
    }
}
