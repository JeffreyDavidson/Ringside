<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use LivewireUI\Modal\ModalComponent;

/**
 * @template TModelForm of BaseForm
 * @template TModelType of Model
 */
abstract class BaseModal extends ModalComponent
{
    protected string $modelTitleField = 'name';

    public static function modalMaxWidth(): string
    {
        return '4xl';
    }

    public static function destroyOnClose(): bool
    {
        return true;
    }

    /** @return class-string<TModelType> */
    abstract protected function getModelClass(): string;

    /** @return TModelForm */
    abstract protected function getModelForm(): BaseForm;

    public function mount(int|string|null $modelId = null): void
    {
        $modelForm = $this->getModelForm();

        if ($modelId === null) {
            Gate::authorize('create', $this->getModelClass());

            $modelForm->reset();

            return;
        }

        $id = is_numeric($modelId) ? (int) $modelId : $modelId;
        $model = $this->findModel($id);

        Gate::authorize('update', $model);

        $modelForm->setModel($model);
    }

    public function getModalTitle(): string
    {
        $modelForm = $this->getModelForm();

        if ($modelForm->modelId !== null) {
            $model = $this->findModel($modelForm->modelId);
            $value = $model->{$this->modelTitleField};

            return __('core.modal.edit', ['name' => (string) ($value ?? 'Unknown')]);
        }

        return __('core.modal.add', ['model' => __("core.models.{$this->modelKey()}")]);
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

    private function modelKey(): string
    {
        return Str::snake(class_basename($this->getModelClass()));
    }

    private function findModel(int|string $modelId): Model
    {
        $modelClass = $this->getModelClass();

        return $modelClass::query()->findOrFail($modelId);
    }
}
