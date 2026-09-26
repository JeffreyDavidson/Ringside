<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Actions\Managers\ClearFromInjuryAction;
use App\Actions\Managers\DeleteAction;
use App\Actions\Managers\EmployAction;
use App\Actions\Managers\InjureAction;
use App\Actions\Managers\ReinstateAction;
use App\Actions\Managers\ReleaseAction;
use App\Actions\Managers\RestoreAction;
use App\Actions\Managers\RetireAction;
use App\Actions\Managers\SuspendAction;
use App\Actions\Managers\UnretireAction;
use App\Builders\Roster\ManagerBuilder;
use App\Enums\Roster\RosterEntityType;
use App\Enums\Roster\RosterLifecycleAction;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Base\Tables\BaseRosterTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Concerns\ExecutesBusinessActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\Managers\Manager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

/** @extends BaseRosterTable<Manager> */
class Main extends BaseRosterTable
{
    use ExecutesBusinessActions;

    #[\Override]
    protected bool $showActionColumn = true;

    #[\Override]
    protected string $databaseTableName = 'managers';

    #[\Override]
    protected string $routeBasePath = 'managers';

    #[\Override]
    protected string $resourceName = 'managers';

    protected function findRosterModel(RosterLifecycleAction $lifecycleAction, int $modelId): Manager
    {
        return $lifecycleAction->usesTrashedModel()
            ? Manager::onlyTrashed()->findOrFail($modelId)
            : Manager::findOrFail($modelId);
    }

    protected function rosterEntityType(): RosterEntityType
    {
        return RosterEntityType::Manager;
    }

    /**
     * @return ManagerBuilder<Manager>
     */
    public function builder(): ManagerBuilder
    {
        return Manager::query()
            ->withEmploymentStatusState()
            ->withFirstEmployment()
            ->oldest('last_name');
    }

    protected function configure(): void
    {
        Gate::authorize('viewAny', Manager::class);
    }

    #[\Override]
    public function render(): View
    {
        return view('livewire.managers.tables.main', [
            'rows' => $this->getRows(),
            'perPageOptions' => $this->perPageAccepted,
            'beforeWrapperView' => $this->beforeWrapperView,
        ]);
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterValues['status'] = '';
        $this->filterValues['employment_date'] = [];
        $this->resetPage();
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('managers.name'), 'full_name')
                ->searchable(function (ManagerBuilder $builder, string $searchTerm): void {
                    $builder->whereNameMatches($searchTerm);
                }),
            Column::make(__('core.status'), 'status')
                ->label(fn (Manager $row) => $row->status->label())
                ->excludeFromColumnSelect(),
            FirstEmploymentDateColumn::make(__('employments.started_at')),
        ];
    }

    protected function getDefaultActionColumn(): Column
    {
        return Column::make(__('core.actions'))
            ->label(fn (Manager $row) => view('components.tables.columns.manager-actions', [
                'manager' => $row,
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
            SelectFilter::make(__('core.status'))
                ->setFilterPillTitle(__('core.status'))
                ->options(EmploymentStatus::filterOptions())
                ->filter(function (ManagerBuilder $builder, string $value): void {
                    /** @var ManagerBuilder<Manager> $builder */
                    $status = EmploymentStatus::tryFrom($value);

                    if ($status !== null) {
                        $builder->whereEmploymentStatus($status);
                    }
                }),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'employments.started_at', 'employments.ended_at'),
        ];
    }

    public function delete(Manager $manager, DeleteAction $deleteAction): void
    {
        Gate::authorize('delete', $manager);

        $this->executeBusinessAction(function () use ($deleteAction, $manager): void {
            $deleteAction->handle($manager);
        }, __('managers.actions.deleted'));
    }

    public function clearFromInjury(Manager $manager, ClearFromInjuryAction $clearFromInjuryAction): void
    {
        $this->executeRosterLifecycleAction(RosterLifecycleAction::ClearFromInjury, $manager->id, fn (Manager $manager) => $clearFromInjuryAction->handle($manager));
    }

    public function employ(Manager $manager, EmployAction $employAction): void
    {
        $this->executeRosterLifecycleAction(RosterLifecycleAction::Employ, $manager->id, fn (Manager $manager) => $employAction->handle($manager));
    }

    public function injure(Manager $manager, InjureAction $injureAction): void
    {
        $this->executeRosterLifecycleAction(RosterLifecycleAction::Injure, $manager->id, fn (Manager $manager) => $injureAction->handle($manager));
    }

    public function reinstate(Manager $manager, ReinstateAction $reinstateAction): void
    {
        $this->executeRosterLifecycleAction(RosterLifecycleAction::Reinstate, $manager->id, fn (Manager $manager) => $reinstateAction->handle($manager));
    }

    public function release(Manager $manager, ReleaseAction $releaseAction): void
    {
        $this->executeRosterLifecycleAction(RosterLifecycleAction::Release, $manager->id, fn (Manager $manager) => $releaseAction->handle($manager));
    }

    public function restore(int $managerId, RestoreAction $restoreAction): void
    {
        if ($this->executeRosterLifecycleAction(RosterLifecycleAction::Restore, $managerId, fn (Manager $manager) => $restoreAction->handle($manager))) {
            $this->redirectRoute('managers.index');
        }
    }

    public function retire(Manager $manager, RetireAction $retireAction): void
    {
        $this->executeRosterLifecycleAction(RosterLifecycleAction::Retire, $manager->id, fn (Manager $manager) => $retireAction->handle($manager));
    }

    public function suspend(Manager $manager, SuspendAction $suspendAction): void
    {
        $this->executeRosterLifecycleAction(RosterLifecycleAction::Suspend, $manager->id, fn (Manager $manager) => $suspendAction->handle($manager));
    }

    public function unretire(Manager $manager, UnretireAction $unretireAction): void
    {
        $this->executeRosterLifecycleAction(RosterLifecycleAction::Unretire, $manager->id, fn (Manager $manager) => $unretireAction->handle($manager));
    }
}
