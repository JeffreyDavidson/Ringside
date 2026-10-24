<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Tables;

use App\Actions\Titles\DeleteAction;
use App\Actions\Titles\RestoreAction;
use App\Builders\Titles\TitleBuilder;
use App\Enums\Titles\TitleStatus;
use App\Enums\Titles\TitleType;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstActivityPeriodColumn;
use App\Livewire\Components\Tables\Filters\FirstActivityPeriodFilter;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Override;

/** @extends BaseTable<Title> */
class Main extends BaseTable
{
    #[Override]
    protected bool $showActionColumn = true;

    #[Override]
    protected string $modelClass = Title::class;

    #[Override]
    protected ?string $indexView = 'livewire.titles.tables.main';

    #[Override]
    protected string $actionsView = 'components.tables.columns.title-actions';

    #[Override]
    protected string $actionsRowVariable = 'title';

    #[Override]
    protected string $databaseTableName = 'titles';

    #[Override]
    protected string $routeBasePath = 'titles';

    #[Override]
    protected string $resourceName = 'titles';

    /** @return TitleBuilder<Title> */
    public function builder(): TitleBuilder
    {
        return Title::query()
            ->withFirstActivityPeriod()
            ->withCurrentChampion()
            ->oldest('name')
            ->orderBy('titles.id');
    }

    #[Override]
    protected function projectRowState(Collection $rows): void
    {
        $rows->loadExists(TitleBuilder::ACTIVITY_STATUS_STATE);
    }

    /** @return array<int, Column> */
    public function columns(): array
    {
        return [
            Column::make(__('titles.name'), 'name')
                ->searchable(),
            Column::make(__('core.status'), 'status')
                ->label(fn (Title $row) => $row->status->label())
                ->excludeFromColumnSelect(),
            Column::make(__('titles.current_champion'), 'champion_name')
                ->label(fn (Title $row) => $row->currentChampionship?->champion->name ?? 'Vacant'),
            FirstActivityPeriodColumn::make(__('activations.started_at')),
        ];
    }

    /** @return array<int, Filter> */
    #[Override]
    public function filters(): array
    {
        return [
            $this->statusFilter(
                TitleStatus::class,
                TitleStatus::filterOptions(),
                function (Builder $builder, TitleStatus $status): void {
                    /** @var TitleBuilder<Title> $builder */
                    $builder->whereStatus($status);
                },
            ),
            SelectFilter::make(__('core.type'), 'type')
                ->options(TitleType::filterOptions())
                ->filter(function (TitleBuilder $builder, string $value): void {
                    $type = TitleType::tryFrom($value);

                    if ($type !== null) {
                        $builder->whereType($type);
                    }
                }),
            FirstActivityPeriodFilter::make(__('core.activation_date'), 'activation_date')->setFields('activityPeriods', 'activity_periods.started_at', 'activity_periods.ended_at'),
        ];
    }

    public function delete(Title $title, DeleteAction $deleteAction): void
    {
        $this->deleteRecord($title, $deleteAction->handle(...), __('titles.actions.deleted'));
    }

    public function restore(int $titleId, RestoreAction $restoreAction): void
    {
        $this->restoreRecord(Title::withTrashed()->findOrFail($titleId), $restoreAction->handle(...), __('titles.actions.restored'));
    }
}
