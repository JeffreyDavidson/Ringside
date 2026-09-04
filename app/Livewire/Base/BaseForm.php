<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Form;
use LogicException;

/**
 * @template TModel of Model
 */
abstract class BaseForm extends Form
{
    #[Locked]
    public int|string|null $modelId = null;

    /** @param TModel|null $formModel */
    public function setModel(?Model $formModel): void
    {
        $modelId = $formModel?->getKey();

        if (! is_int($modelId) && ! is_string($modelId) && $modelId !== null) {
            throw new LogicException('Livewire forms require integer or string model keys.');
        }

        $this->modelId = $modelId;

        if ($formModel !== null) {
            $this->fill($formModel->getAttributes());
            $this->loadModelData($formModel);
        }
    }

    public function isCreating(): bool
    {
        return $this->modelId === null;
    }

    public function isEditing(): bool
    {
        return $this->modelId !== null;
    }

    /** @param TModel $model */
    protected function loadModelData(Model $model): void {}

    /** @return array<string, array<int, mixed>> */
    abstract protected function rules(): array;

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [];
    }
}
