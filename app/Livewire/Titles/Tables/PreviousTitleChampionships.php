<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Table\Column;
use App\Livewire\Table\DataTableComponent;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Queries\Titles\TitleChampionshipQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use LogicException;

/** @extends DataTableComponent<TitleChampionship> */
class PreviousTitleChampionships extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'title_championships';

    protected string $resourceName = 'title championships';

    /**
     * Undocumented variable.
     */
    public ?int $titleId;

    public function configure(): void
    {
        Gate::authorize('viewList', Title::class);
    }

    /**
     * @return Builder<TitleChampionship>
     */
    public function builder(): Builder
    {
        if (! isset($this->titleId)) {
            throw new LogicException('A title was not provided.');
        }

        return TitleChampionship::query()
            ->forTitleId($this->titleId)
            ->previous()
            ->mostRecentlyLostFirst();
    }

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('championships.new_champion'), 'current_champion'),
            Column::make(__('championships.previous_champion'), 'former_champion'),
            Column::make(__('championships.dates_held'), 'dates_held'),
            Column::make(__('championships.days_held'))
                ->label(fn (TitleChampionship $row): int => TitleChampionshipQuery::reignLengthInDays($row)),
        ];
    }
}
