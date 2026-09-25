<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Pages;

use App\Actions\Wrestlers\CreateAction;
use App\Actions\Wrestlers\UpdateAction;
use App\Livewire\Wrestlers\Forms\CreateEditForm;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class FormPage extends Component
{
    public CreateEditForm $form;

    public function mount(?Wrestler $wrestler = null): void
    {
        Gate::authorize($wrestler instanceof Wrestler ? 'update' : 'create', $wrestler ?? Wrestler::class);

        $this->form->setModel($wrestler);
    }

    public function save(CreateAction $createAction, UpdateAction $updateAction): void
    {
        if ($this->form->isEditing()) {
            $wrestler = $this->form->wrestler();
            Gate::authorize('update', $wrestler);
            $this->form->validate();
            $updateAction->handle($wrestler, $this->form->toData());

            session()->flash('status', 'Wrestler updated.');
        } else {
            Gate::authorize('create', Wrestler::class);
            $this->form->validate();
            $createAction->handle($this->form->toData());

            session()->flash('status', 'Wrestler added to the roster.');
        }

        $this->redirectRoute('wrestlers.index');
    }

    public function render(): View
    {
        return view('livewire.wrestlers.pages.form-page', [
            'title' => $this->form->isEditing()
                ? 'Edit '.$this->form->wrestler()->name
                : 'Add Wrestler',
        ]);
    }
}
