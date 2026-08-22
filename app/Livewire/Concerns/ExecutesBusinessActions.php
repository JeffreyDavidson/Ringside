<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Exceptions\BaseBusinessException;
use Closure;

trait ExecutesBusinessActions
{
    /** @param Closure(): void $action */
    protected function executeBusinessAction(Closure $action, ?string $successMessage = null): bool
    {
        try {
            $action();
        } catch (BaseBusinessException $exception) {
            $message = $exception->getMessage();

            session()->flash('error', $message);
            $this->dispatch('flash-message', type: 'error', message: $message);

            return false;
        }

        if ($successMessage !== null) {
            session()->flash('status', $successMessage);
            $this->dispatch('flash-message', type: 'status', message: $successMessage);
        }

        return true;
    }
}
