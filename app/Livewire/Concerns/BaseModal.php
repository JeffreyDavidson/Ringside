<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Livewire\Base\LivewireBaseForm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use LivewireUI\Modal\ModalComponent;

class BaseModal extends ModalComponent
{
    public ?Model $model;

    protected string $modelTitleField = 'name';

    /** @var class-string */
    protected string $modelType;

    protected LivewireBaseForm $modelForm;

    /** @var non-falsy-string */
    protected string $modalFormPath;

    public function mount(?int $modelId = null): void
    {
        if (isset($modelId)) {
            try {
                $this->model = $this->modelType::findOrFail($modelId);
                $this->modelForm->setModel($this->model);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }
    }

    public function getModalTitle(): string
    {
        if (isset($this->modelForm) && isset($this->modelForm->formModel)) {
            return 'Edit '.($this->modelForm->formModel->{$this->modelTitleField} ?? 'Unknown');
        }

        return 'Add '.class_basename($this->modelType);
    }

    public function clear(): void
    {
        if (isset($this->model) && ! is_null($this->model)) {
            $this->modelForm->setModel($this->model);
        } else {
            $this->modelForm->reset();
        }
    }

    public function save(): void
    {
        if ($this->modelForm->store()) {
            $this->dispatch('refreshDatatable');

            $this->closeModal();
        }
    }

    public function render(): View
    {
        /** @var non-falsy-string $view */
        $view = 'livewire.'.$this->modalFormPath;

        return view($view);
    }
}
