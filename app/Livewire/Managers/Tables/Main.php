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
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Livewire\Concerns\ExecutesSoftDeleteActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Manager> */
class Main extends BaseTable
{
    use ExecutesRosterActions;
    use ExecutesSoftDeleteActions;

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
            ->with('firstEmployment')
            ->oldest('last_name');
    }

    public function configure(): void
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
                ->options([
                    '' => __('core.all'),
                    'employed' => 'Employed',
                    'future_employment' => 'Awaiting Employment',
                    'released' => 'Released',
                    'unemployed' => 'Unemployed',
                    'retired' => 'Retired',
                ])
                ->filter(function (ManagerBuilder $builder, string $value): void {
                    /** @var ManagerBuilder<Manager> $builder */
                    match ($value) {
                        'employed' => $builder->employed(),
                        'future_employment' => $builder->futureEmployed(),
                        'released' => $builder->released(),
                        'unemployed' => $builder->unemployed(),
                        'retired' => $builder->retired(),
                        default => null,
                    };
                }),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'employments.started_at', 'employments.ended_at'),
        ];
    }

    public function delete(Manager $manager): void
    {
        Gate::authorize('delete', $manager);

        $this->executeSoftDeleteAction(function () use ($manager): void {
            resolve(DeleteAction::class)->handle($manager);
        }, 'Manager successfully deleted.');
    }

    public function clearFromInjury(Manager $manager): void
    {
        $this->executeManagerAction(RosterLifecycleAction::ClearFromInjury, $manager->id);
    }

    public function employ(Manager $manager): void
    {
        $this->executeManagerAction(RosterLifecycleAction::Employ, $manager->id);
    }

    public function injure(Manager $manager): void
    {
        $this->executeManagerAction(RosterLifecycleAction::Injure, $manager->id);
    }

    public function reinstate(Manager $manager): void
    {
        $this->executeManagerAction(RosterLifecycleAction::Reinstate, $manager->id);
    }

    public function release(Manager $manager): void
    {
        $this->executeManagerAction(RosterLifecycleAction::Release, $manager->id);
    }

    public function restore(int $managerId): void
    {
        $this->executeManagerAction(RosterLifecycleAction::Restore, $managerId);
    }

    public function retire(Manager $manager): void
    {
        $this->executeManagerAction(RosterLifecycleAction::Retire, $manager->id);
    }

    public function suspend(Manager $manager): void
    {
        $this->executeManagerAction(RosterLifecycleAction::Suspend, $manager->id);
    }

    public function unretire(Manager $manager): void
    {
        $this->executeManagerAction(RosterLifecycleAction::Unretire, $manager->id);
    }

    private function executeManagerAction(RosterLifecycleAction $lifecycleAction, int $managerId): void
    {
        $manager = $lifecycleAction === RosterLifecycleAction::Restore
            ? Manager::onlyTrashed()->findOrFail($managerId)
            : Manager::findOrFail($managerId);

        match ($lifecycleAction) {
            RosterLifecycleAction::Employ => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Manager, $manager, fn () => resolve(EmployAction::class)->handle($manager)),
            RosterLifecycleAction::Release => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Manager, $manager, fn () => resolve(ReleaseAction::class)->handle($manager)),
            RosterLifecycleAction::Retire => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Manager, $manager, fn () => resolve(RetireAction::class)->handle($manager)),
            RosterLifecycleAction::Unretire => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Manager, $manager, fn () => resolve(UnretireAction::class)->handle($manager)),
            RosterLifecycleAction::Suspend => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Manager, $manager, fn () => resolve(SuspendAction::class)->handle($manager)),
            RosterLifecycleAction::Reinstate => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Manager, $manager, fn () => resolve(ReinstateAction::class)->handle($manager)),
            RosterLifecycleAction::Injure => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Manager, $manager, fn () => resolve(InjureAction::class)->handle($manager)),
            RosterLifecycleAction::ClearFromInjury => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Manager, $manager, fn () => resolve(ClearFromInjuryAction::class)->handle($manager)),
            RosterLifecycleAction::Restore => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Manager, $manager, fn () => resolve(RestoreAction::class)->handle($manager)),
        };
    }
}
