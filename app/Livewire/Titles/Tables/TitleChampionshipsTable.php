<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Tables;

use App\Livewire\Table\Column;
use App\Livewire\Table\DataTableComponent;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Queries\Titles\TitleChampionshipQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TitleChampionshipsTable extends DataTableComponent
{
    /**
     * Undocumented variable.
     */
    public ?Title $title = null;

    /**
     * Undocumented function.
     */
    public function mount(?Title $title = null): void
    {
        $this->title = $title;

        parent::mount();
    }

    public function configure(): void {}

    /**
     * Return the query builder for title championship rows.
     *
     * @return Builder<Model>
     */
    public function builder(): Builder
    {
        if ($this->title instanceof Title) {
            /** @var Builder<Model> $builder */
            $builder = $this->title->championships()->getQuery();

            return $builder;
        }

        /** @var Builder<Model> $builder */
        $builder = TitleChampionship::query();

        return $builder;
    }

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('title_championships.current_champion'), 'current_champion'),
            Column::make(__('title_championships.former_champion'), 'former_champion'),
            Column::make(__('title_championships.dates_held'), 'dates_held'),
            Column::make(__('title_championships.reign_length'))
                ->label(fn (TitleChampionship $row): int => TitleChampionshipQuery::reignLengthInDays($row)),
        ];
    }
}
