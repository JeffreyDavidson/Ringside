<?php

declare(strict_types=1);

namespace App\Livewire\Titles;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\Title;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Validate;

class TitleForm extends LivewireBaseForm
{
    protected string $formModelType = Title::class;

    public ?Title $formModel;

    #[Validate('required|string|max:255|ends_with:Title,Titles', as: 'titles.name')]
    public string $name = '';

    #[Validate('nullable|date', as: 'activations.started_at')]
    public Carbon|string|null $start_date = '';

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
