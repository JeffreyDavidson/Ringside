<?php

declare(strict_types=1);

namespace App\Livewire\Managers;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\Managers\Manager;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Support\Carbon;

/**
 * @extends LivewireBaseForm<ManagerForm, ?Manager>
 */
class ManagerForm extends LivewireBaseForm
{
    public $formModel;

    public string $first_name = '';

    public string $last_name = '';

    public Carbon|string|null $start_date = '';

    public function loadExtraData(): void
    {
        $this->start_date = $this->formModel?->firstEmployment?->started_at->toDateString();
    }

    public function store(): bool
    {
        $this->validate();

        if ($this->formModel === null) {
            $this->formModel = new Manager([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
            ]);
            $this->formModel->save();
        } else {
            $this->formModel->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
            ]);
        }

        return true;
    }

    /**
     * @return array<string, list<EmploymentStartDateCanBeChanged|string>>
     */
    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date', new EmploymentStartDateCanBeChanged($this->formModel)],
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
