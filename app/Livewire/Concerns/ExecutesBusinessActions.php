<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Exceptions\BaseBusinessException;
use Closure;

trait ExecutesBusinessActions
{
    use DispatchesActionFeedback;

    /** @param Closure(): void $action */
    protected function executeBusinessAction(Closure $action, ?string $successMessage = null): bool
    {
        try {
            $action();
        } catch (BaseBusinessException $exception) {
            $message = $exception->getMessage();

            $this->dispatchActionFailure($message);

            return false;
        }

        if ($successMessage !== null) {
            $this->dispatchActionSuccess($successMessage);
        }

        return true;
    }
}
