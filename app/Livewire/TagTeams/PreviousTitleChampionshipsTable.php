<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams;

use App\Models\TagTeam;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class PreviousTitleChampionshipsTable extends DataTableComponent
{
    /**
     * Tag team to use for component.
     */
    public TagTeam $tagTeam;

    /**
     * Undocumented function.
     */
    public function mount(TagTeam $tagTeam): void
    {
        $this->tagTeam = $tagTeam;
    }

    public function configure(): void {}

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('titles.name'), 'name'),
            Column::make(__('championships.previous_champion'), 'previous_champion'),
            Column::make(__('championships.dates_held'), 'dates_held'),
            Column::make(__('championships.reign_length'), 'reign_length'),
        ];
    }
}
