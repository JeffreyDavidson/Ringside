<?php

declare(strict_types=1);

namespace App\Livewire\Managers;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\Manager;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Validate;

class ManagerForm extends LivewireBaseForm
{
    protected string $formModelType = Manager::class;

    public ?Manager $formModel;

    #[Validate('required|string|max:255', as: 'managers.first_name')]
    public string $first_name = '';

    #[Validate('required|string|max:255', as: 'managers.last_name')]
    public string $last_name = '';

    #[Validate('nullable|date', as: 'employments.started_at')]
    public Carbon|string|null $start_date = '';

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
