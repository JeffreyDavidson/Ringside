<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Tables;

use App\Actions\Referees\ClearFromInjuryAction;
use App\Actions\Referees\DeleteAction;
use App\Actions\Referees\EmployAction;
use App\Actions\Referees\InjureAction;
use App\Actions\Referees\ReinstateAction;
use App\Actions\Referees\ReleaseAction;
use App\Actions\Referees\RestoreAction;
use App\Actions\Referees\RetireAction;
use App\Actions\Referees\SuspendAction;
use App\Actions\Referees\UnretireAction;
use App\Builders\Roster\RefereeBuilder;
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
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Referee> */
class Main extends BaseTable
{
    use ExecutesBusinessActions;
    use ExecutesRosterActions;

    protected bool $showActionColumn = true;

    protected string $databaseTableName = 'referees';

    protected string $routeBasePath = 'referees';

    protected string $resourceName = 'referees';

    /** @return RefereeBuilder<Referee> */
    public function builder(): RefereeBuilder
    {
        return Referee::query()
            ->withEmploymentStatusState()
            ->with('firstEmployment')
            ->oldest('last_name');
    }

    protected function configure(): void
    {
        Gate::authorize('viewAny', Referee::class);
    }

    /** @return array<int, Column> */
    public function columns(): array
    {
        return [
            Column::make(__('referees.name'), 'full_name')
                ->searchable(function (RefereeBuilder $builder, string $searchTerm): void {
                    $builder->whereNameMatches($searchTerm);
                }),
            Column::make(__('core.status'), 'status')
                ->label(fn (Referee $row) => $row->status->label())
                ->excludeFromColumnSelect(),
            FirstEmploymentDateColumn::make(__('employments.started_at')),
        ];
    }

    /** @return array<int, Filter> */
    public function filters(): array
    {
        return [
            SelectFilter::make(__('core.status'))
                ->setFilterPillTitle(__('core.status'))
                ->options(EmploymentStatus::filterOptions())
                ->filter(function (RefereeBuilder $builder, string $value): void {
                    /** @var RefereeBuilder<Referee> $builder */
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

    public function delete(Referee $referee): void
    {
        Gate::authorize('delete', $referee);

        $this->executeBusinessAction(function () use ($referee): void {
            resolve(DeleteAction::class)->handle($referee);
        }, __('referees.actions.deleted'));
    }

    public function clearFromInjury(Referee $referee): void
    {
        $this->executeRefereeAction(RosterLifecycleAction::ClearFromInjury, $referee->id);
    }

    public function employ(Referee $referee): void
    {
        $this->executeRefereeAction(RosterLifecycleAction::Employ, $referee->id);
    }

    public function injure(Referee $referee): void
    {
        $this->executeRefereeAction(RosterLifecycleAction::Injure, $referee->id);
    }

    public function reinstate(Referee $referee): void
    {
        $this->executeRefereeAction(RosterLifecycleAction::Reinstate, $referee->id);
    }

    public function release(Referee $referee): void
    {
        $this->executeRefereeAction(RosterLifecycleAction::Release, $referee->id);
    }

    public function restore(int $refereeId): void
    {
        if ($this->executeRefereeAction(RosterLifecycleAction::Restore, $refereeId)) {
            $this->redirectRoute('referees.index');
        }
    }

    public function retire(Referee $referee): void
    {
        $this->executeRefereeAction(RosterLifecycleAction::Retire, $referee->id);
    }

    public function suspend(Referee $referee): void
    {
        $this->executeRefereeAction(RosterLifecycleAction::Suspend, $referee->id);
    }

    public function unretire(Referee $referee): void
    {
        $this->executeRefereeAction(RosterLifecycleAction::Unretire, $referee->id);
    }

    private function executeRefereeAction(RosterLifecycleAction $lifecycleAction, int $refereeId): bool
    {
        $referee = $lifecycleAction->usesTrashedModel()
            ? Referee::onlyTrashed()->findOrFail($refereeId)
            : Referee::query()->findOrFail($refereeId);

        return match ($lifecycleAction) {
            RosterLifecycleAction::Employ => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Referee, $referee, fn () => resolve(EmployAction::class)->handle($referee)),
            RosterLifecycleAction::Release => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Referee, $referee, fn () => resolve(ReleaseAction::class)->handle($referee)),
            RosterLifecycleAction::Retire => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Referee, $referee, fn () => resolve(RetireAction::class)->handle($referee)),
            RosterLifecycleAction::Unretire => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Referee, $referee, fn () => resolve(UnretireAction::class)->handle($referee)),
            RosterLifecycleAction::Suspend => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Referee, $referee, fn () => resolve(SuspendAction::class)->handle($referee)),
            RosterLifecycleAction::Reinstate => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Referee, $referee, fn () => resolve(ReinstateAction::class)->handle($referee)),
            RosterLifecycleAction::Injure => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Referee, $referee, fn () => resolve(InjureAction::class)->handle($referee)),
            RosterLifecycleAction::ClearFromInjury => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Referee, $referee, fn () => resolve(ClearFromInjuryAction::class)->handle($referee)),
            RosterLifecycleAction::Restore => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Referee, $referee, fn () => resolve(RestoreAction::class)->handle($referee)),
        };
    }
}
