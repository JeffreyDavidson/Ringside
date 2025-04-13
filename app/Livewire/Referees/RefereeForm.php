<?php

declare(strict_types=1);

namespace App\Livewire\Referees;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\Referee;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Validate;

/**
 * @extends LivewireBaseForm<RefereeForm, ?Referee>
 */
final class RefereeForm extends LivewireBaseForm
{
    public $formModel;

    #[Validate]
    public string $first_name = '';

    #[Validate]
    public string $last_name = '';

    #[Validate]
    public Carbon|string|null $start_date = '';

    public function loadExtraData(): void
    {
        $this->start_date = $this->formModel?->firstEmployment?->started_at->toDateString();
    }

    public function store(): bool
    {
        $this->validate();

        if ($this->formModel === null) {
            $this->formModel = new Referee([
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
