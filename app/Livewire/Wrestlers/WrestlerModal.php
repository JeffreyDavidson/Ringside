<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers;

use App\Models\Wrestler;
use Illuminate\View\View;
use LivewireUI\Modal\ModalComponent;

class WrestlerModal extends ModalComponent
{
    public ?Wrestler $wrestler;

    public WrestlerForm $form;

    public function mount(?int $wrestlerId = null): void
    {
        if (isset($wrestlerId)) {
            $this->wrestler = Wrestler::find($wrestlerId);
            $this->form->setWrestler($this->wrestler);
        }
    }

    public function save(): void
    {
        if ($this->form->update()) {
            $this->dispatch('refreshDatatable');

            $this->closeModal();
        }
    }

    public function render(): View
    {
        return view('livewire.wrestlers.modal');
    }
}
