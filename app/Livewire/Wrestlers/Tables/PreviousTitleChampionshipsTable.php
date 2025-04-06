<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\Title;
use App\Models\TitleChampionship;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\CountColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class PreviousTitleChampionshipsTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'tittle_championships';

    protected string $resourceName = 'title championships';

    /**
     * Wrestler to use for component.
     */
    public ?int $wrestlerId;

    /**
     * @return Builder<TitleChampionship>
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new \Exception("You didn't specify a wrestler");
        }

        return TitleChampionship::query()
            ->whereHasMorph(
                'newChampion',
                [Wrestler::class],
                function (Builder $query) {
                    $query->whereIn('id', [$this->wrestlerId]);
                }
            );
    }

    public function configure(): void {}

    /**
     * @return array<int, Column>
     **/
    public function columns(): array
    {
        return [
            LinkColumn::make(__('titles.name'))
                ->title(fn (Title $row) => $row->name)
                ->location(fn (Title $row) => route('titles.show', $row)),
            LinkColumn::make(__('championships.previous_champion'))
                ->title(fn (TitleChampionship $row) => $row->previousChampion->name ?? '')
                ->location(fn (Wrestler $row) => route('wrestlers.show', $row)),
            Column::make(__('championships.dates_held'), 'dates_held'),
            // CountColumn::make(__('championships.days_held'))
            //     ->setDataSource('days_held'),
        ];
    }
}
