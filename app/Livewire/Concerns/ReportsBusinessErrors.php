<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Exceptions\BaseBusinessException;
use Closure;

/** For screens that keep the user's unsaved input and show a refused save as an error on a field. */
trait ReportsBusinessErrors
{
    use ExplainsKeptInput;

    /** The field that shows a business rule failure from the Action; null lets the exception propagate. */
    protected ?string $businessErrorField = null;

    /**
     * Run a save callback and turn a business rule failure into an error on the screen.
     *
     * @param  Closure(): void  $save
     * @return bool false when the save was refused
     */
    protected function reportBusinessErrors(Closure $save): bool
    {
        try {
            $save();
        } catch (BaseBusinessException $exception) {
            $this->addError($this->businessErrorField($exception), $this->failureMessageKeepingInput($exception));

            return false;
        }

        return true;
    }

    /** The field that shows the failure; override to choose it from the exception. */
    protected function businessErrorField(BaseBusinessException $exception): string
    {
        return $this->businessErrorField ?? throw $exception;
    }
}
