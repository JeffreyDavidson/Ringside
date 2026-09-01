<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Support\RosterResourceRouteResolver;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\Columns\LinkColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Titles\TitleChampionship;
use App\Queries\Titles\TitleChampionshipQuery;

/**
 * @extends DataTableComponent<TitleChampionship>
 */
abstract class BasePreviousTitleChampionshipsTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'titles_championships';

    protected string $resourceName = 'title championships';

    protected RosterResourceRouteResolver $routeResolver;

    public function boot(RosterResourceRouteResolver $routeResolver): void
    {
        $this->routeResolver = $routeResolver;
    }

    protected function configure(): void
    {
        $this->addAdditionalSelects([
            'titles_championships.title_id',
            'titles_championships.won_at',
            'titles_championships.lost_at',
        ]);
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('titles.name'))
                ->title(fn (TitleChampionship $row): string => $this->titleName($row))
                ->location(fn (TitleChampionship $row): ?string => $row->title === null
                    ? null
                    : route('titles.show', $row->title)),
            LinkColumn::make(__('championships.previous_champion'))
                ->title(fn (TitleChampionship $row) => $row->previousChampionship?->champion->name ?? 'N/A')
                ->location(function (TitleChampionship $row): ?string {
                    $champion = $row->previousChampionship?->champion;

                    return $champion === null ? null : $this->routeResolver->urlFor($champion);
                }),
            DateColumn::make(__('championships.dates_held'), 'won_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('championships.dates_held'), 'lost_at')
                ->outputFormat('Y-m-d'),
            Column::make(__('championships.days_held'))
                ->label(fn (TitleChampionship $row): int => TitleChampionshipQuery::reignLengthInDays($row)),
        ];
    }

    private function titleName(TitleChampionship $championship): string
    {
        $title = $championship->title;

        return $title->name ?? 'N/A';
    }
}
