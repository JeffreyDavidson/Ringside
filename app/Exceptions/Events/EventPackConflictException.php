<?php

declare(strict_types=1);

namespace App\Exceptions\Events;

use App\Exceptions\BaseBusinessException;

final class EventPackConflictException extends BaseBusinessException
{
    public static function reviewRequired(): static
    {
        return new self(__('event-pack.review_required'));
    }
}
