<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Actions\Stables\DeleteAction;
use App\Builders\Roster\StableBuilder;
use App\Enums\Stables\StableStatus;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstActivityPeriodColumn;
use App\Livewire\Components\Tables\Filters\FirstActivityPeriodFilter;
use App\Livewire\Concerns\ExecutesBusinessActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\Stables\Stable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Stable> */
class Main extends BaseTable
{
    use ExecutesBusinessActions;

    #[\Override]
    protected bool $showActionColumn = true;

    #[\Override]
    protected string $databaseTableName = 'stables';

    #[\Override]
    protected string $routeBasePath = 'stables';

    #[\Override]
    protected string $resourceName = 'stables';

    /** @return StableBuilder<Stable> */
    public function builder(): StableBuilder
    {
        return Stable::query()
            ->withFirstActivityPeriod()
            ->with(['currentWrestlers', 'currentTagTeams'])
            ->oldest('name')
            ->orderBy('stables.id');
    }

    #[\Override]
    protected function projectRowState(Collection $rows): void
    {
        $rows->loadExists(StableBuilder::ACTIVITY_STATUS_STATE);
    }

    protected function configure(): void
    {
        Gate::authorize('viewAny', Stable::class);
    }

    #[\Override]
    public function render(): View
    {
        return view('livewire.stables.tables.main', [
            'rows' => $this->getRows(),
            'perPageOptions' => $this->perPageAccepted,
        ]);
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('stables.name'), 'name')
                ->searchable(),
            Column::make(__('core.status'), 'status')
                ->label(fn (Stable $row) => $row->status->label())
                ->excludeFromColumnSelect(),
            FirstActivityPeriodColumn::make(__('activations.started_at')),
        ];
    }

    protected function getDefaultActionColumn(): Column
    {
        return Column::make(__('core.actions'))
            ->label(fn (Stable $row) => view('components.tables.columns.stable-actions', [
                'stable' => $row,
            ])->render())
            ->html()
            ->excludeFromColumnSelect();
    }

    /**
     * @return array<int, Filter>
     */
    #[\Override]
    public function filters(): array
    {
        return [
            SelectFilter::make('Status', 'status')
                ->options([
                    '' => 'All',
                    StableStatus::Unformed->value => StableStatus::Unformed->label(),
                    StableStatus::PendingEstablishment->value => StableStatus::PendingEstablishment->label(),
                    StableStatus::Active->value => StableStatus::Active->label(),
                    StableStatus::Inactive->value => StableStatus::Inactive->label(),
                    StableStatus::Retired->value => StableStatus::Retired->label(),
                ])
                ->filter(function (Builder $builder, string $value): void {
                    /** @var StableBuilder<Stable> $builder */
                    $status = StableStatus::tryFrom($value);

                    if ($status !== null) {
                        $builder->whereStatus($status);
                    }
                }),
            FirstActivityPeriodFilter::make('Activation Date')->setFields('activityPeriods', 'activity_periods.started_at', 'activity_periods.ended_at'),
        ];
    }

    public function delete(Stable $stable, DeleteAction $deleteAction): void
    {
        Gate::authorize('delete', $stable);

        $this->executeBusinessAction(function () use ($deleteAction, $stable): void {
            $deleteAction->handle($stable);
        }, __('stables.actions.deleted'));

        $this->forgetMetadata();
    }
}
