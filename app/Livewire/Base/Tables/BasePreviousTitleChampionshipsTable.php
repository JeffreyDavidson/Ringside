<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\TitleChampionship;
use Illuminate\Database\Eloquent\Model;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\CountColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

final class BasePreviousTitleChampionshipsTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'title_championships';

    protected string $resourceName = 'title championships';

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'title_championships.title_id',
            'title_championships.won_at',
            'title_championships.lost_at',
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
                ->title(fn (TitleChampionship $row) => $row->previousChampion->name ?? 'N/A')
                ->location(function (Model $row) {
                    $previousChampion = $row->previousChampion;

                    if ($previousChampion) {
                        $type = str($previousChampion->getMorphClass())->kebab()->plural();

                        return route($type.'.show', $previousChampion);
                    }

                    return 'N/A';
                }),
            DateColumn::make(__('championships.dates_held'), 'won_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('championships.dates_held'), 'lost_at')
                ->outputFormat('Y-m-d'),
            // CountColumn::make(__('championships.days_held'))
            //     ->setDataSource('lengthInDays'),
        ];
    }
}
