<?php

declare(strict_types=1);

namespace App\Livewire\EventMatches;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\EventMatch;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * @extends LivewireBaseForm<EventMatchForm, ?EventMatch>
 */
class EventMatchForm extends LivewireBaseForm
{
    public $formModel;

    public ?int $matchTypeId;

    /**
     * @var array<int, int>
     */
    public array $titles = [];

    /**
     * @var array<int, int>
     */
    public array $referees = [];

    /**
     * @var array<int, int>
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
        /** @var array<int, int> $referees */
        $referees = $this->formModel?->referees->pluck('id')->toArray();

        /** @var array<int, int> $titles */
        $titles = $this->formModel?->titles->pluck('id')->toArray();

        $this->matchTypeId = $this->formModel?->matchType?->id;
        $this->referees = $referees;
        $this->titles = $titles;
    }

    public function store(): bool
    {
        $this->validate();

        return true;
    }
}
