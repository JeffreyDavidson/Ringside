<?php

declare(strict_types=1);

namespace App\Livewire\Tournaments\Forms;

use App\Data\Tournaments\TournamentData;
use App\Enums\Tournaments\EntrantKind;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentEntry;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

class CreateEdit extends Form
{
    #[Locked]
    public ?string $requestId = null;

    public string $name = '';

    public ?string $description = null;

    public string $kind = 'wrestler';

    public int $capacity = 8;

    public bool $repeatAppearances = false;

    /** @var list<int|string> */
    public array $entrants = [];

    public function load(Tournament $tournament): void
    {
        $this->name = $tournament->name;
        $this->description = $tournament->description;
        $this->kind = $tournament->entrant_kind->value;
        $this->capacity = $tournament->capacity;
        $this->repeatAppearances = $tournament->repeat_appearances;
        $this->entrants = array_values($tournament->entries()->get()->map(fn (TournamentEntry $entry): int => $entry->competitor_id)->all());
    }

    public function data(): TournamentData
    {
        $this->validate([...$this->namingRules(), 'requestId' => ['nullable', 'uuid'], 'kind' => ['required', Rule::enum(EntrantKind::class)], 'capacity' => [Rule::in([4, 8, 16])], 'repeatAppearances' => ['boolean'], 'entrants' => ['required', 'list', 'min:2', 'max:'.$this->capacity], 'entrants.*' => ['integer', 'distinct']]);

        return new TournamentData($this->name, $this->description, EntrantKind::from($this->kind), $this->capacity, $this->repeatAppearances, array_map(intval(...), $this->entrants), $this->requestId);
    }

    /** Validates only the name and description, for renaming a tournament that is already running. */
    public function validateNaming(): void
    {
        $this->validate($this->namingRules());
    }

    public function move(int $index, int $direction): void
    {
        if (! in_array($direction, [-1, 1], true) || ! isset($this->entrants[$index],$this->entrants[$index + $direction])) {
            return;
        }
        $entrants = $this->entrants;
        [$entrants[$index],$entrants[$index + $direction]] = [$entrants[$index + $direction], $entrants[$index]];
        $this->entrants = array_values($entrants);
    }

    /** @return array<string, list<string>> */
    private function namingRules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000']];
    }
}
