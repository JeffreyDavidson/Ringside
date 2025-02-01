<?php

declare(strict_types=1);

namespace App\Livewire\Titles;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\Title;
use App\Rules\ActivationStartDateCanBeChanged;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class TitleForm extends LivewireBaseForm
{
    protected string $formModelType = Title::class;

    public ?Title $formModel;

    public string $name = '';

    public Carbon|string|null $start_date = '';

    /**
     * @return array<string, list<Unique|ActivationStartDateCanBeChanged|string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'ends_with:Title,Titles', Rule::unique('titles', 'name')->ignore($this->formModel ?? '')],
            'start_date' => ['nullable', 'date', new ActivationStartDateCanBeChanged($this->formModel ?? '')],
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

    public function loadExtraData(): void
    {
        $this->start_date = $this->formModel->firstActivation?->started_at->toDateString();
    }

    public function store(): bool
    {
        $this->validate();

        if (! isset($this->formModel)) {
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
}
