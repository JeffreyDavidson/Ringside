<?php

declare(strict_types=1);

namespace App\Exceptions;

final class RecordChangedException extends BaseBusinessException
{
    public static function changed(): static
    {
        return new self(__('core.errors.record.changed'));
    }
}
