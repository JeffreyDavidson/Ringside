<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Form;

/**
 * @template TForm of BaseForm
 * @template TFormModel of Model|null
 */
abstract class BaseForm extends Form
{
    /** @var TFormModel */
    protected ?Model $formModel = null;

    #[Locked]
    public int|string|null $modelId = null;

    /** @param TFormModel $formModel */
    public function setModel(?Model $formModel): void
    {
        $this->formModel = $formModel;
        $this->modelId = $formModel?->getKey();

        if ($formModel !== null) {
            $this->fill($formModel->getAttributes());
        }

        $this->loadExtraData();
    }

    public function isCreating(): bool
    {
        return $this->modelId === null;
    }

    public function isEditing(): bool
    {
        return $this->modelId !== null;
    }

    public function generateModelEditName(string $fieldName): string
    {
        if ($this->formModel === null) {
            return 'Unknown';
        }

        $value = $this->formModel->{$fieldName};

        return (string) ($value ?? 'Unknown');
    }

    protected function loadExtraData(): void {}

    /** @throws ValidationException */
    public function store(): bool
    {
        $this->validate();

        return $this->storeModel();
    }

    protected function storeModel(): bool
    {
        if ($this->isCreating()) {
            $modelClass = $this->getModelClass();
            $this->formModel = $modelClass::query()->create($this->getModelData());
            $this->modelId = $this->formModel->getKey();

            return true;
        }

        if ($this->formModel === null) {
            $modelClass = $this->getModelClass();
            $this->formModel = $modelClass::query()->findOrFail($this->modelId);
        }

        $this->formModel->update($this->getModelData());

        return true;
    }

    /** @return array<string, mixed> */
    abstract protected function getModelData(): array;

    /** @return array<string, array<int, mixed>> */
    abstract protected function rules(): array;

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [];
    }

    /** @return class-string<Model> */
    abstract protected function getModelClass(): string;
}
