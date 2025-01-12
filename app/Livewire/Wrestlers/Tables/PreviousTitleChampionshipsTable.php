<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\TitleChampionship;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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
    public Wrestler $wrestler;

    /**
     * Undocumented function.
     */
    public function mount(Wrestler $wrestler): void
    {
        $this->wrestler = $wrestler;
    }

    public function builder(): Builder
    {
        return TitleChampionship::query()
            ->withWhereHas('wrestlers', function ($query) {
                $query->whereIn('wrestler_id', [$this->wrestler->id]);
            });
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'title_championships.won_at',
            'title_championships.lost_at',
            'pivot.hired_at as hired_at',
            'pivot.left_at as left_at',
            DB::raw('DATEDIFF(COALESCE(lost_at, NOW()), won_at) AS days_held_count'),
        ]);
    }

    public function columns(): array
    {
        return [
            LinkColumn::make(__('titles.name'))
                ->title(fn ($row) => $row->name)
                ->location(fn ($row) => route('titles.show', $row)),
            LinkColumn::make(__('championships.previous_champion'))
                ->title(fn ($row) => $row->previousChampion->name)
                ->location(fn ($row) => route('wrestlers.show', $row)),
            Column::make(__('championships.dates_held'), 'dates_held'),
            CountColumn::make(__('championships.days_held'))
                ->setDataSource('days_held'),
        ];
    }
}
