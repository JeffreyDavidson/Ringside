<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use App\Livewire\Concerns\GeneratesDummyData;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TForm of BaseForm
 * @template TModel of Model
 *
 * @extends BaseModal<TForm, TModel>
 */
abstract class BaseFormModal extends BaseModal
{
    use GeneratesDummyData;

    abstract protected function getFormClass(): string;

    abstract protected function getModelClass(): string;

    abstract protected function getModalPath(): string;

    public bool $isModalOpen = false;

    public function openModal(mixed $modelId = null): void
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

        if (! $this->storeForm()) {
            return false;
        }

        $this->dispatch('refreshDatatable');
        $this->closeModal();
        $this->dispatch('closeModal');
        $this->dispatch('form-submitted');

        return true;
    }

    protected function storeForm(): bool
    {
        return $this->form->store();
    }

    public function mount(mixed $modelId = null): void
    {
        $this->modalFormPath = $this->getModalPath();

        $modelClass = $this->getModelClass();
        $this->modelType = new $modelClass();

        if (! isset($this->form)) {
            $formClass = $this->getFormClass();
            $this->form = new $formClass($this, 'form');
        }

        $this->modelForm = $this->form;

        parent::mount($modelId);
    }
}
