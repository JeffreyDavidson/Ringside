<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\Title;
use App\Models\TitleChampionship;
use Illuminate\Database\Eloquent\Model;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\CountColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class BasePreviousTitleChampionshipsTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'title_championships';

    protected string $resourceName = 'title championships';

    public function configure(): void {}

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('titles.name'))
                ->title(fn (Title $row) => $row->name)
                ->location(fn (Title $row) => route('titles.show', $row)),
            LinkColumn::make(__('championships.previous_champion'))
                ->title(fn (TitleChampionship $row) => $row->previousChampion->name ?? '')
                ->location(fn (Model $row) => route('wrestlers.show', $row)),
            Column::make(__('championships.dates_held'), 'dates_held'),
            CountColumn::make(__('championships.days_held'))
                ->setDataSource('days_held'),
        ];
    }
}
