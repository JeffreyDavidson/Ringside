<?php

declare(strict_types=1);

namespace App\Exceptions;

final class RecordChangedException extends BaseBusinessException
{
    public static function changed(): static
    {
        return new self(__('core.errors.record.changed'));
    }

    /** For screens that keep the user's unsaved input after the failure. */
    public static function changedKeepingInput(): static
    {
        $message = __('core.errors.record.changed').' '.__('core.errors.record.input_kept');

        return new self($message);
    }
}
