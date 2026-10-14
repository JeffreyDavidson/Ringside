<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum PublicAnnouncement: string
{
    case Scheduled = 'scheduled';
    case Rescheduled = 'rescheduled';
    case Cancelled = 'cancelled';
    case Postponed = 'postponed';

    public function label(): string
    {
        return __('public-events.'.$this->value);
    }

    public function isNotice(): bool
    {
        return in_array($this, [self::Cancelled, self::Postponed], true);
    }
}
