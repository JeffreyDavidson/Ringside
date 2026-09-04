<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use Illuminate\Database\Eloquent\Model;
use LivewireUI\Modal\ModalComponent;
use LogicException;

/**
 * @template TModelForm of BaseForm
 * @template TModelType of Model
 */
abstract class BaseModal extends ModalComponent
{
    protected string $modelTitleField = 'name';

    /** @return class-string<TModelType> */
    abstract protected function getModelClass(): string;

    /** @return TModelForm */
    abstract protected function getModelForm(): BaseForm;

    public function mount(int|string|null $modelId = null): void
    {
        $modelForm = $this->getModelForm();

        if ($modelId === null) {
            $modelForm->reset();

            return;
        }

        $id = is_numeric($modelId) ? (int) $modelId : $modelId;
        $modelForm->setModel($this->findModel($id));
    }

    public function getModalTitle(): string
    {
        $modelForm = $this->getModelForm();

        if ($modelForm->modelId !== null) {
            $model = $this->findModel($modelForm->modelId);
            $value = $model->{$this->modelTitleField};

            return 'Edit '.(string) ($value ?? 'Unknown');
        }

        return 'Add '.class_basename($this->getModelClass());
    }

    public function clear(): void
    {
        $modelForm = $this->getModelForm();

        if ($modelForm->modelId !== null) {
            $modelForm->setModel($this->findModel($modelForm->modelId));

            return;
        }

        $modelForm->reset();
    }

    /** @return TModelType */
    private function findModel(int|string $modelId): Model
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::query()->findOrFail($modelId);

        if (! $model instanceof $modelClass) {
            throw new LogicException("Expected an instance of {$modelClass}.");
        }

        return $model;
    }
}
