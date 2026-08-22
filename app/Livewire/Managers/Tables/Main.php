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
            ->with('firstEmployment')
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
                    match (EmploymentStatus::tryFrom($value)) {
                        EmploymentStatus::Employed => $builder->employed(),
                        EmploymentStatus::FutureEmployment => $builder->futureEmployed(),
                        EmploymentStatus::Released => $builder->released(),
                        EmploymentStatus::Unemployed => $builder->unemployed(),
                        EmploymentStatus::Retired => $builder->retired(),
                        default => null,
                    };
                }),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'employments.started_at', 'employments.ended_at'),
        ];
    }

    public function delete(Manager $manager): void
    {
        Gate::authorize('delete', $manager);

        $this->executeBusinessAction(function () use ($manager): void {
            resolve(DeleteAction::class)->handle($manager);
        }, __('managers.actions.deleted'));
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
        if ($this->executeManagerAction(RosterLifecycleAction::Restore, $managerId)) {
            $this->redirectRoute('managers.index');
        }
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

    private function executeManagerAction(RosterLifecycleAction $lifecycleAction, int $managerId): bool
    {
        $manager = $lifecycleAction->usesTrashedModel()
            ? Manager::onlyTrashed()->findOrFail($managerId)
            : Manager::findOrFail($managerId);

        return match ($lifecycleAction) {
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
