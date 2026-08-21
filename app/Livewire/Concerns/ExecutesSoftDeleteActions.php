<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Exceptions\BaseBusinessException;
use Closure;

trait ExecutesSoftDeleteActions
{
    /** @param Closure(): void $action */
    protected function executeSoftDeleteAction(Closure $action, string $successMessage): bool
    {
        try {
            $action();
        } catch (BaseBusinessException $exception) {
            session()->flash('error', $exception->getMessage());

            return false;
        }

        session()->flash('status', $successMessage);

        return true;
    }
}
