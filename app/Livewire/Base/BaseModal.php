<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use Illuminate\Database\Eloquent\Model;
use LivewireUI\Modal\ModalComponent;

/**
 * @template TModelForm of BaseForm
 * @template TModelType of Model
 */
abstract class BaseModal extends ModalComponent
{
    /** @var TModelType|null */
    protected ?Model $model = null;

    /** @var TModelForm */
    protected BaseForm $modelForm;

    /** @var TModelType */
    protected Model $modelType;

    protected string $modalFormPath;

    protected string $modelTitleField = 'name';

    public function mount(int|string|null $modelId = null): void
    {
        if ($modelId === null) {
            $this->model = null;
            $this->modelForm->reset();

            return;
        }

        $id = is_numeric($modelId) ? (int) $modelId : $modelId;
        $this->model = $this->modelType::query()->findOrFail($id);
        $this->modelForm->setModel($this->model);
    }

    public function getModalTitle(): string
    {
        if ($this->model !== null) {
            return 'Edit '.$this->modelForm->generateModelEditName($this->modelTitleField);
        }

        return 'Add '.(isset($this->modelType) ? class_basename($this->modelType) : 'Record');
    }

    public function clear(): void
    {
        if ($this->model !== null) {
            $this->modelForm->setModel($this->model);

            return;
        }

        $this->modelForm->reset();
    }
}
