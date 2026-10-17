<?php

declare(strict_types=1);

namespace App\Exceptions\Events;

use App\Exceptions\BaseBusinessException;

final class TalentBookingException extends BaseBusinessException
{
    public static function confirm(): self
    {
        return new self(__('talent-bookings.errors.confirm'));
    }

    public static function fee(): self
    {
        return new self(__('talent-bookings.errors.fee'));
    }

    public static function person(): self
    {
        return new self(__('talent-bookings.errors.person'));
    }

    public static function reasonRequired(): self
    {
        return new self(__('talent-bookings.errors.reason'));
    }

    public static function reopen(): self
    {
        return new self(__('talent-bookings.errors.reopen'));
    }
}
