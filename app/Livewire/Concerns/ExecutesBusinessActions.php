<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Exceptions\BaseBusinessException;
use Closure;

trait ExecutesBusinessActions
{
    use DispatchesActionFeedback;

    /**
     * @param  Closure(): void  $action
     * @param  Closure(BaseBusinessException): string|null  $failureMessage  Resolves the message shown for a refused action; defaults to the exception message.
     */
    protected function executeBusinessAction(Closure $action, ?string $successMessage = null, ?Closure $failureMessage = null): bool
    {
        try {
            $action();
        } catch (BaseBusinessException $exception) {
            $message = $failureMessage instanceof Closure
                ? $failureMessage($exception)
                : $exception->getMessage();

            $this->dispatchActionFailure($message);

            return false;
        }

        if ($successMessage !== null) {
            $this->dispatchActionSuccess($successMessage);
        }

        return true;
    }
}
