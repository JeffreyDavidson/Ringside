<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Actions\Managers\DeleteAction;
use App\Actions\Managers\EmployAction;
use App\Actions\Managers\HealAction;
use App\Actions\Managers\InjureAction;
use App\Actions\Managers\ReinstateAction;
use App\Actions\Managers\ReleaseAction;
use App\Actions\Managers\RestoreAction;
use App\Actions\Managers\RetireAction;
use App\Actions\Managers\SuspendAction;
use App\Actions\Managers\UnretireAction;
use App\Builders\Roster\ManagerBuilder;
use App\Enums\Roster\RosterEntityType;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Manager> */
class Main extends BaseTable
{
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

        resolve(DeleteAction::class)->handle($manager);
        session()->flash('status', 'Manager successfully deleted.');
    }

    public function clearFromInjury(Manager $manager): void
    {
        $this->handleManagerAction('heal', $manager->id);
    }

    public function employ(Manager $manager): void
    {
        $this->handleManagerAction('employ', $manager->id);
    }

    public function injure(Manager $manager): void
    {
        $this->handleManagerAction('injure', $manager->id);
    }

    public function reinstate(Manager $manager): void
    {
        $this->handleManagerAction('reinstate', $manager->id);
    }

    public function release(Manager $manager): void
    {
        $this->handleManagerAction('release', $manager->id);
    }

    public function restore(int $managerId): void
    {
        $this->handleManagerAction('restore', $managerId);
    }

    public function retire(Manager $manager): void
    {
        $this->handleManagerAction('retire', $manager->id);
    }

    public function suspend(Manager $manager): void
    {
        $this->handleManagerAction('suspend', $manager->id);
    }

    public function unretire(Manager $manager): void
    {
        $this->handleManagerAction('unretire', $manager->id);
    }

    public function handleManagerAction(string $action, int $managerId): void
    {
        $manager = $action === 'restore'
            ? Manager::onlyTrashed()->findOrFail($managerId)
            : Manager::findOrFail($managerId);

        match ($action) {
            'employ' => $this->executeAuthorizedRosterAction('employ', 'employed', RosterEntityType::Manager, $manager, fn () => resolve(EmployAction::class)->handle($manager)),
            'release' => $this->executeAuthorizedRosterAction('release', 'released', RosterEntityType::Manager, $manager, fn () => resolve(ReleaseAction::class)->handle($manager)),
            'retire' => $this->executeAuthorizedRosterAction('retire', 'retired', RosterEntityType::Manager, $manager, fn () => resolve(RetireAction::class)->handle($manager)),
            'unretire' => $this->executeAuthorizedRosterAction('unretire', 'unretired', RosterEntityType::Manager, $manager, fn () => resolve(UnretireAction::class)->handle($manager)),
            'suspend' => $this->executeAuthorizedRosterAction('suspend', 'suspended', RosterEntityType::Manager, $manager, fn () => resolve(SuspendAction::class)->handle($manager)),
            'reinstate' => $this->executeAuthorizedRosterAction('reinstate', 'reinstated', RosterEntityType::Manager, $manager, fn () => resolve(ReinstateAction::class)->handle($manager)),
            'injure' => $this->executeAuthorizedRosterAction('injure', 'injured', RosterEntityType::Manager, $manager, fn () => resolve(InjureAction::class)->handle($manager)),
            'heal' => $this->executeAuthorizedRosterAction('clearFromInjury', 'healed', RosterEntityType::Manager, $manager, fn () => resolve(HealAction::class)->handle($manager)),
            'restore' => $this->executeAuthorizedRosterAction('restore', 'restored', RosterEntityType::Manager, $manager, fn () => resolve(RestoreAction::class)->handle($manager)),
            default => null,
        };
    }
}
