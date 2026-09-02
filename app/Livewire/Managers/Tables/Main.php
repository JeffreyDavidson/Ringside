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
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Concerns\ExecutesBusinessActions;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\Managers\Manager;
use Closure;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Manager> */
class Main extends BaseTable
{
    use ExecutesBusinessActions;
    use ExecutesRosterActions;

    protected bool $showActionColumn = true;

    protected string $databaseTableName = 'managers';

    protected string $routeBasePath = 'managers';

    protected string $resourceName = 'managers';

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

    /**
     * @return array<int, Filter>
     */
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
        $this->executeManagerAction(RosterLifecycleAction::ClearFromInjury, $manager->id, fn (Manager $manager) => $clearFromInjuryAction->handle($manager));
    }

    public function employ(Manager $manager, EmployAction $employAction): void
    {
        $this->executeManagerAction(RosterLifecycleAction::Employ, $manager->id, fn (Manager $manager) => $employAction->handle($manager));
    }

    public function injure(Manager $manager, InjureAction $injureAction): void
    {
        $this->executeManagerAction(RosterLifecycleAction::Injure, $manager->id, fn (Manager $manager) => $injureAction->handle($manager));
    }

    public function reinstate(Manager $manager, ReinstateAction $reinstateAction): void
    {
        $this->executeManagerAction(RosterLifecycleAction::Reinstate, $manager->id, fn (Manager $manager) => $reinstateAction->handle($manager));
    }

    public function release(Manager $manager, ReleaseAction $releaseAction): void
    {
        $this->executeManagerAction(RosterLifecycleAction::Release, $manager->id, fn (Manager $manager) => $releaseAction->handle($manager));
    }

    public function restore(int $managerId, RestoreAction $restoreAction): void
    {
        if ($this->executeManagerAction(RosterLifecycleAction::Restore, $managerId, fn (Manager $manager) => $restoreAction->handle($manager))) {
            $this->redirectRoute('managers.index');
        }
    }

    public function retire(Manager $manager, RetireAction $retireAction): void
    {
        $this->executeManagerAction(RosterLifecycleAction::Retire, $manager->id, fn (Manager $manager) => $retireAction->handle($manager));
    }

    public function suspend(Manager $manager, SuspendAction $suspendAction): void
    {
        $this->executeManagerAction(RosterLifecycleAction::Suspend, $manager->id, fn (Manager $manager) => $suspendAction->handle($manager));
    }

    public function unretire(Manager $manager, UnretireAction $unretireAction): void
    {
        $this->executeManagerAction(RosterLifecycleAction::Unretire, $manager->id, fn (Manager $manager) => $unretireAction->handle($manager));
    }

    /** @param Closure(Manager): void $action */
    private function executeManagerAction(RosterLifecycleAction $lifecycleAction, int $managerId, Closure $action): bool
    {
        $manager = $lifecycleAction->usesTrashedModel()
            ? Manager::onlyTrashed()->findOrFail($managerId)
            : Manager::findOrFail($managerId);

        return match ($lifecycleAction) {
            RosterLifecycleAction::Employ,
            RosterLifecycleAction::Release,
            RosterLifecycleAction::Retire,
            RosterLifecycleAction::Unretire,
            RosterLifecycleAction::Suspend,
            RosterLifecycleAction::Reinstate,
            RosterLifecycleAction::Injure,
            RosterLifecycleAction::ClearFromInjury,
            RosterLifecycleAction::Restore => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Manager, $manager, fn () => $action($manager)),
        };
    }
}
