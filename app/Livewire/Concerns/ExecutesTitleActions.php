<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Exceptions\BaseBusinessException;
use Closure;

trait ExecutesTitleActions
{
    /** @param Closure(): void $action */
    protected function executeTitleAction(Closure $action): bool
    {
        try {
            $action();
        } catch (BaseBusinessException $exception) {
            session()->flash('error', $exception->getMessage());

            return false;
        }

        return true;
    }
}
