<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Tables;

use App\Builders\TitleBuilder;
use App\Enums\ActivationStatus;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\Title;
use App\View\Columns\FirstActivationDateColumn;
use App\View\Filters\FirstActivationFilter;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class TitlesTable extends BaseTableWithActions
{
    use HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'titles';

    protected string $routeBasePath = 'titles';

    protected string $resourceName = 'titles';

    /**
     * @return TitleBuilder<Title>
     */
    public function builder(): TitleBuilder
    {
        return Title::query()
            ->with(['currentActivation'])
            ->oldest('name');
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
            Column::make(__('titles.name'), 'name')
                ->searchable(),
            $this->getDefaultStatusColumn(),
            // Column::make(__('titles.current_champion'), 'champion_name'),
            FirstActivationDateColumn::make(__('activations.started_at')),
        ];
    }

    /**
     * Undocumented function
     *
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        /** @var array<string, string> $statuses */
        $statuses = collect(ActivationStatus::cases())->pluck('name', 'value')->toArray();

        return [
            $this->getDefaultStatusFilter($statuses),
            FirstActivationFilter::make('Activation Date')->setFields('activations', 'titles_activations.started_at', 'titles_activations.ended_at'),
        ];
    }

    public function delete(Title $title): void
    {
        $this->deleteModel($title);
    }
}
