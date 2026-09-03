<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use App\Livewire\Concerns\GeneratesDummyData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use LogicException;

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

    public bool $isModalOpen = false;

    protected ?string $createdEventName = null;

    protected ?string $updatedEventName = null;

    protected bool $resetFormAfterSubmission = false;

    public function openModal(int|string|null $modelId = null): void
    {
        $this->mount($modelId);
        $this->authorizeFormAccess();
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
        $wasCreating = $this->form->isCreating();

        if ($this->model !== null) {
            $this->form->setModel($this->model);
        }

        $this->authorizeFormAccess();

        if (! $this->storeForm()) {
            return false;
        }

        $this->dispatch('refreshDatatable');
        $this->closeModal();
        $this->dispatch('closeModal');
        $this->dispatch('form-submitted');

        $eventName = $wasCreating
            ? $this->createdEventName
            : $this->updatedEventName;

        if ($eventName !== null) {
            $this->dispatch($eventName);
        }

        if ($this->resetFormAfterSubmission) {
            $this->form->reset();
        }

        return true;
    }

    protected function storeForm(): bool
    {
        $this->form->validate();

        if ($this->form->isEditing()) {
            $this->updateForm();

            return true;
        }

        $this->createForm();

        return true;
    }

    protected function createForm(): void
    {
        throw new LogicException('A form modal must define createForm().');
    }

    protected function updateForm(): void
    {
        throw new LogicException('A form modal must define updateForm().');
    }

    private function authorizeFormAccess(): void
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
        parent::mount($modelId);
    }

    /** @return TForm */
    protected function getModelForm(): BaseForm
    {
        return $this->form;
    }
}
