<?php

declare(strict_types=1);

namespace App\Livewire\Managers;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\Manager;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Support\Carbon;

class ManagerForm extends LivewireBaseForm
{
    protected string $formModelType = Manager::class;

    public ?Manager $formModel;

    public string $first_name = '';

    public string $last_name = '';

    public Carbon|string|null $start_date = '';

    /**
     * @return array<string, list<EmploymentStartDateCanBeChanged|string>>
     */
    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date', new EmploymentStartDateCanBeChanged($this->formModel ?? '')],
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
        $this->start_date = $this->formModel->firstEmployment?->started_at->toDateString();
    }

    public function store(): bool
    {
        $this->validate();

        if (! isset($this->formModel)) {
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
}
