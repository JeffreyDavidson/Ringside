<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Exceptions\BaseBusinessException;
use App\Exceptions\RecordChangedException;

/** For screens that keep the user's unsaved input after a refused save. */
trait ExplainsKeptInput
{
    /** The failure message, telling the user their input is still on screen when the record changed underneath them. */
    protected function failureMessageKeepingInput(BaseBusinessException $exception): string
    {
        if (! $exception instanceof RecordChangedException) {
            return $exception->getMessage();
        }

        return "{$exception->getMessage()} ".__('core.errors.record.input_kept');
    }
}
