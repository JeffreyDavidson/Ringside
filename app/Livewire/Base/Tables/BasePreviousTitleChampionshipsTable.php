<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Model;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

abstract class BasePreviousTitleChampionshipsTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'titles_championships';

    protected string $resourceName = 'title championships';

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'titles_championships.title_id',
            'titles_championships.won_at',
            'titles_championships.lost_at',
        ]);
    }

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('titles.name'))
                ->title(fn (TitleChampionship $row) => $row->title->name)
                ->location(fn (TitleChampionship $row) => route('titles.show', $row->title)),
            LinkColumn::make(__('championships.previous_champion'))
                ->title(fn (TitleChampionship $row) => 'N/A') // TODO: Implement previous champion lookup
                ->location(function (Model $row) {
                    // TODO: Implement previous champion navigation
                    return null;
                }),
            DateColumn::make(__('championships.dates_held'), 'won_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('championships.dates_held'), 'lost_at')
                ->outputFormat('Y-m-d'),
            Column::make(__('championships.days_held'))
                ->label(fn (TitleChampionship $row) => $row->lengthInDays()),
        ];
    }
}
