<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\TagTeam;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * @extends LivewireBaseForm<TagTeamForm, ?TagTeam>
 */
class TagTeamForm extends LivewireBaseForm
{
    public $formModel;

    public string $name = '';

    public ?string $signature_move = '';

    public Carbon|string|null $start_date = '';

    public ?int $wrestlerA;

    public ?int $wrestlerB;

    public function loadExtraData(): void
    {
        $currentWrestlers = $this->formModel?->currentWrestlers;

        $this->start_date = $this->formModel?->hasEmployments() ? $this->formModel->firstEmployment?->started_at->toDateString() : '';
        $this->wrestlerA = ! is_null($currentWrestlers) && $currentWrestlers->isNotEmpty() ? $currentWrestlers->first()->id : null;
        $this->wrestlerB = ! is_null($currentWrestlers) && $currentWrestlers->isNotEmpty() ? $currentWrestlers->last()->id : null;
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

    /**
     * @return array<string, list<Unique|Exists|EmploymentStartDateCanBeChanged|string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('tag_teams', 'name')],
            'signature_move' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'string', 'date', new EmploymentStartDateCanBeChanged($this->formModel)],
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
}
