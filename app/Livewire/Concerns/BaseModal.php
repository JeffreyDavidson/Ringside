<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Livewire\Base\LivewireBaseForm;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use LivewireUI\Modal\ModalComponent;

/**
 * @template TModelForm of LivewireBaseForm
 * @template TModelType of Model
 */
abstract class BaseModal extends ModalComponent
{
    protected ?Model $model;

    /**
     * @var TModelForm
     */
    protected $modelForm;

    /**
     * @var TModelType
     */
    protected $modelType;

    protected string $modalFormPath;

    protected string $modelTitleField;

    protected string $titleField;

    public function mount(?int $modelId = null): void
    {
        if (isset($modelId)) {
            try {
                $this->model = $this->modelType::findOrFail($modelId);
                $this->modelForm->setModel($this->model);
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
        }
    }

    public function getModalTitle(): string
    {
        if (isset($this->model)) {
            return 'Edit '.$this->modelForm->generateModelEditName($this->modelTitleField);
        }

        return 'Add '.class_basename($this->modelType);
    }

    public function clear(): void
    {
        if (! is_null($this->model)) {
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

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        $view = 'livewire.'.$this->modalFormPath;

        if (! view()->exists($view)) {
            $view = 'blank';
        }

        return view($view);
    }
}
