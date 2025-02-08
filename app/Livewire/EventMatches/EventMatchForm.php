<?php

declare(strict_types=1);

namespace App\Livewire\EventMatches;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\EventMatch;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class EventMatchForm extends LivewireBaseForm
{
    protected string $formModelType = EventMatch::class;

    public ?EventMatch $formModel;

    public ?int $matchTypeId;

    /**
     * @var array<int, string>
     */
    public array $titles = [];

    /**
     * @var array<int, string>
     */
    public array $referees = [];

    /**
     * @var array<int, string>
     */
    public array $competitors = [];

    public string $preview = '';

    /**
     * @return array<string, list<Exists|string>>
     */
    protected function rules(): array
    {
        return [
            'matchTypeId' => ['required', 'integer', Rule::exists('match_types')],
            'referees' => ['required', 'array'],
            'titles' => ['required', 'array'],
            'competitors' => ['required', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'matchTypeId' => 'match type',
        ];
    }

    public function loadExtraData(): void
    {
        $this->matchTypeId = $this->formModel?->matchType->id;
        $this->referees = $this->formModel?->referees->pluck('id', 'name')->all();
        $this->titles = $this->formModel?->titles->pluck('id', 'name')->all();
    }

    public function store(): bool
    {
        $this->validate();

        return true;
    }
}
