<?php

declare(strict_types=1);

namespace App\Exceptions\Events;

use App\Exceptions\BaseBusinessException;

final class TalentBookingException extends BaseBusinessException
{
    public static function because(string $reason): self
    {
        return new self(__('talent-bookings.errors.'.$reason));
    }
}
