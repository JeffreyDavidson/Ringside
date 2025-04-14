<?php

declare(strict_types=1);

namespace App\Livewire\Titles;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\Title;
use App\Rules\ActivationStartDateCanBeChanged;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Validate;

/**
 * @extends LivewireBaseForm<TitleForm, ?Title>
 */
final class TitleForm extends LivewireBaseForm
{
    public $formModel;

    #[Validate]
    public string $name = '';

    #[Validate]
    public Carbon|string|null $start_date = '';

    public function loadExtraData(): void
    {
        $this->start_date = $this->formModel?->firstActivation?->started_at->toDateString();
    }

    public function store(): bool
    {
        $this->validate();

        if ($this->formModel === null) {
            $this->formModel = new Title([
                'name' => $this->name,
            ]);
            $this->formModel->save();
        } else {
            $this->formModel->update([
                'name' => $this->name,
            ]);
        }

        return true;
    }

    /**
     * @return array<string, list<Unique|ActivationStartDateCanBeChanged|string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'ends_with:Title,Titles', Rule::unique('titles', 'name')->ignore($this->formModel)],
            'start_date' => ['nullable', 'date', new ActivationStartDateCanBeChanged($this->formModel)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'start_date' => 'start date',
        ];
    }
}
