<?php

declare(strict_types=1);

namespace App\Livewire\Stables;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\Stable;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Validate;

class StableForm extends LivewireBaseForm
{
    protected string $formModelType = Stable::class;

    public ?Stable $formModel;

    #[Validate('required|string|min:5|max:255', as: 'stables.name')]
    public string $name = '';

    #[Validate('nullable|date', as: 'activations.started_at')]
    public Carbon|string|null $start_date = '';

    public function loadExtraData(): void
    {
        $this->start_date = $this->formModel?->firstActivation?->started_at->toDateString();
    }

    public function store(): bool
    {
        $this->validate();

        if (! isset($this->formModel)) {
            $this->formModel = new Stable([
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
