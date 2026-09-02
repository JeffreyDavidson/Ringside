<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Actions\Stables\DeleteAction;
use App\Actions\Stables\DisbandAction;
use App\Actions\Stables\EstablishAction;
use App\Actions\Stables\RestoreAction;
use App\Actions\Stables\RetireAction;
use App\Actions\Stables\UnretireAction;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Stable> */
class Main extends BaseTable
{
    use ExecutesBusinessActions;

    protected bool $showActionColumn = true;

    protected string $databaseTableName = 'stables';

    protected string $routeBasePath = 'stables';

    protected string $resourceName = 'stables';

    /** @return StableBuilder<Stable> */
    public function builder(): StableBuilder
    {
        return Stable::query()
            ->withActivityStatusState()
            ->withFirstActivityPeriod()
            ->oldest('name');
    }

    protected function configure(): void
    {
        Gate::authorize('viewAny', Stable::class);
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

    /**
     * @return array<int, Filter>
     */
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
    }

    /**
     * Establish a stable.
     */
    public function establish(Stable $stable, EstablishAction $establishAction): void
    {
        Gate::authorize('establish', $stable);

        if ($this->executeBusinessAction(function () use ($establishAction, $stable): void {
            $establishAction->handle($stable);
        })) {
            $this->redirectRoute('stables.index');
        }
    }

    /**
     * Disband a stable.
     */
    public function disband(Stable $stable, DisbandAction $disbandAction): void
    {
        Gate::authorize('disband', $stable);

        if ($this->executeBusinessAction(function () use ($disbandAction, $stable): void {
            $disbandAction->handle($stable);
        })) {
            $this->redirectRoute('stables.index');
        }
    }

    /**
     * Restore a stable.
     */
    public function restore(int $stableId, RestoreAction $restoreAction): void
    {
        $stable = Stable::onlyTrashed()->findOrFail($stableId);

        Gate::authorize('restore', $stable);

        if ($this->executeBusinessAction(function () use ($restoreAction, $stable): void {
            $restoreAction->handle($stable);
        })) {
            $this->redirectRoute('stables.index');
        }
    }

    /**
     * Retire a stable.
     */
    public function retire(Stable $stable, RetireAction $retireAction): void
    {
        Gate::authorize('retire', $stable);

        if ($this->executeBusinessAction(function () use ($retireAction, $stable): void {
            $retireAction->handle($stable);
        })) {
            $this->redirectRoute('stables.index');
        }
    }

    /**
     * Unretire a stable.
     */
    public function unretire(Stable $stable, UnretireAction $unretireAction): void
    {
        Gate::authorize('unretire', $stable);

        if ($this->executeBusinessAction(function () use ($unretireAction, $stable): void {
            $unretireAction->handle($stable);
        })) {
            $this->redirectRoute('stables.index');
        }
    }
}
