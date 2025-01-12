<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\TagTeam;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Validate;

class TagTeamForm extends LivewireBaseForm
{
    protected string $formModelType = TagTeam::class;

    public ?TagTeam $formModel;

    #[Validate('required|string|min:5|max:255|unique:tag_teams,name', as: 'tag-teams.name')]
    public string $name = '';

    #[Validate('nullable|string|max:255', as: 'tag-teams.signature_move')]
    public ?string $signature_move = '';

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
            $this->formModel = new TagTeam([
                'name' => $this->name,
                'signature_move' => $this->signature_move,
            ]);
            $this->formModel->save();
        } else {
            $this->formModel->update([
                'name' => $this->name,
                'signature_move' => $this->signature_move,
            ]);
        }

        return true;
    }
}
