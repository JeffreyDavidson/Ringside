<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

trait DispatchesActionFeedback
{
    protected function dispatchActionSuccess(string $message): void
    {
        session()->flash('status', $message);
        $this->dispatch('flash-message', type: 'status', message: $message);
    }

    protected function dispatchActionWarning(string $message): void
    {
        $this->dispatch('flash-message', type: 'warning', message: $message);
    }

    protected function dispatchActionFailure(string $message): void
    {
        session()->flash('error', $message);
        $this->dispatch('flash-message', type: 'error', message: $message);
    }
}
