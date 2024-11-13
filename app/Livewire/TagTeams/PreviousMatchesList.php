<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams;

use App\Models\TagTeam;
use Illuminate\Contracts\View\View;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class PreviousMatchesList extends DataTableComponent
{
    /**
     * Tag Team to use for component.
     */
    public TagTeam $tagTeam;

    /**
     * Set the Tag Team to be used for this component.
     */
    public function mount(TagTeam $tagTeam): void
    {
        $this->tagTeam = $tagTeam;
    }

    public function configure(): void
    {
    }

    public function columns(): array
    {
        return [
            Column::make(__('events.name'), 'name'),
            Column::make(__('events.date'), 'date'),
            Column::make(__('matches.opponents'), 'opponents'),
            Column::make(__('matches.titles'), 'titles'),
            Column::make(__('matches.result'), 'result'),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->tagTeam
            ->previousMatches();

        $previousMatches = $query->paginate();

        return view('livewire.tag-teams.previous-matches.previous-matches-list', [
            'previousMatches' => $previousMatches,
        ]);
    }
}
