<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\TagTeam;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

class TagTeamForm extends LivewireBaseForm
{
    protected string $formModelType = TagTeam::class;

    public ?TagTeam $formModel;

    public string $name = '';

    public ?string $signature_move = '';

    public Carbon|string|null $start_date = '';

    public int $wrestlerA;

    public int $wrestlerB;

    /**
     * @return array<string, list<Unique|Exists|string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('tag_teams', 'name')],
            'signature_move' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'string', 'date'],
            'wrestlerA' => [
                'nullable',
                'bail',
                'integer',
                'different:wrestlerB',
                'required_with:start_date',
                'required_with:wrestlerB',
                'required_with:signature_move',
                Rule::exists('wrestlers', 'id'),
            ],
            'wrestlerB' => [
                'nullable',
                'bail',
                'integer',
                'different:wrestlerA',
                'required_with:start_date',
                'required_with:wrestlerA',
                'required_with:signature_move',
                Rule::exists('wrestlers', 'id'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'signature_move' => 'signature move',
            'start_date' => 'start date',
            'wrestlerA' => 'tag team partner A',
            'wrestlerB' => 'tag team partner B',
        ];
    }

    public function loadExtraData(): void
    {
        $currentWrestlers = $this->formModel->currentWrestlers;

        $this->start_date = $this->formModel->firstEmployment?->started_at->toDateString();
        $this->wrestlerA = $currentWrestlers->first();
        $this->wrestlerB = $currentWrestlers->last();
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
