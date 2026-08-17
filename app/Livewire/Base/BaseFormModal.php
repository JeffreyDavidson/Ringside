<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use App\Livewire\Concerns\GeneratesDummyData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * @template TForm of BaseForm
 * @template TModel of Model
 *
 * @property TForm $form
 *
 * @extends BaseModal<TForm, TModel>
 */
abstract class BaseFormModal extends BaseModal
{
    use GeneratesDummyData;

    /** @return class-string<TModel> */
    abstract protected function getModelClass(): string;

    public bool $isModalOpen = false;

    public function openModal(int|string|null $modelId = null): void
    {
        $this->mount($modelId);
        $this->isModalOpen = true;
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
    }

    public function save(): void
    {
        $this->submitForm();
    }

    public function submitForm(): bool
    {
        if ($this->model !== null) {
            $this->form->setModel($this->model);
        }

        $this->authorizeSubmission();

        if (! $this->storeForm()) {
            return false;
        }

        $this->dispatch('refreshDatatable');
        $this->closeModal();
        $this->dispatch('closeModal');
        $this->dispatch('form-submitted');

        return true;
    }

    abstract protected function storeForm(): bool;

    private function authorizeSubmission(): void
    {
        $modelClass = $this->getModelClass();

        if ($this->form->isCreating()) {
            Gate::authorize('create', $modelClass);

            return;
        }

        Gate::authorize('update', $modelClass::query()->findOrFail($this->form->modelId));
    }

    public function mount(int|string|null $modelId = null): void
    {
        $this->modelClass = $this->getModelClass();

        $this->modelForm = $this->form;

        parent::mount($modelId);
    }
}
