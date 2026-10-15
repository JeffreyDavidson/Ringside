<?php

declare(strict_types=1);

namespace App\Exceptions\Events;

use App\Exceptions\BaseBusinessException;

final class EventPublicationException extends BaseBusinessException
{
    public static function poster(): static
    {
        return new self(__('public-events.poster_invalid'));
    }

    public static function invalid(): static
    {
        return new self(__('public-events.invalid'));
    }
}
