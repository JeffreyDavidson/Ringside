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
use Closure;
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
            ->withFirstEmployment()
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
                    $status = EmploymentStatus::tryFrom($value);

                    if ($status !== null) {
                        $builder->whereEmploymentStatus($status);
                    }
                }),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'employments.started_at', 'employments.ended_at'),
        ];
    }

    public function delete(Referee $referee, DeleteAction $deleteAction): void
    {
        Gate::authorize('delete', $referee);

        $this->executeBusinessAction(function () use ($deleteAction, $referee): void {
            $deleteAction->handle($referee);
        }, __('referees.actions.deleted'));
    }

    public function clearFromInjury(Referee $referee, ClearFromInjuryAction $clearFromInjuryAction): void
    {
        $this->executeRefereeAction(RosterLifecycleAction::ClearFromInjury, $referee->id, fn (Referee $referee) => $clearFromInjuryAction->handle($referee));
    }

    public function employ(Referee $referee, EmployAction $employAction): void
    {
        $this->executeRefereeAction(RosterLifecycleAction::Employ, $referee->id, fn (Referee $referee) => $employAction->handle($referee));
    }

    public function injure(Referee $referee, InjureAction $injureAction): void
    {
        $this->executeRefereeAction(RosterLifecycleAction::Injure, $referee->id, fn (Referee $referee) => $injureAction->handle($referee));
    }

    public function reinstate(Referee $referee, ReinstateAction $reinstateAction): void
    {
        $this->executeRefereeAction(RosterLifecycleAction::Reinstate, $referee->id, fn (Referee $referee) => $reinstateAction->handle($referee));
    }

    public function release(Referee $referee, ReleaseAction $releaseAction): void
    {
        $this->executeRefereeAction(RosterLifecycleAction::Release, $referee->id, fn (Referee $referee) => $releaseAction->handle($referee));
    }

    public function restore(int $refereeId, RestoreAction $restoreAction): void
    {
        if ($this->executeRefereeAction(RosterLifecycleAction::Restore, $refereeId, fn (Referee $referee) => $restoreAction->handle($referee))) {
            $this->redirectRoute('referees.index');
        }
    }

    public function retire(Referee $referee, RetireAction $retireAction): void
    {
        $this->executeRefereeAction(RosterLifecycleAction::Retire, $referee->id, fn (Referee $referee) => $retireAction->handle($referee));
    }

    public function suspend(Referee $referee, SuspendAction $suspendAction): void
    {
        $this->executeRefereeAction(RosterLifecycleAction::Suspend, $referee->id, fn (Referee $referee) => $suspendAction->handle($referee));
    }

    public function unretire(Referee $referee, UnretireAction $unretireAction): void
    {
        $this->executeRefereeAction(RosterLifecycleAction::Unretire, $referee->id, fn (Referee $referee) => $unretireAction->handle($referee));
    }

    /** @param Closure(Referee): void $action */
    private function executeRefereeAction(RosterLifecycleAction $lifecycleAction, int $refereeId, Closure $action): bool
    {
        $referee = $lifecycleAction->usesTrashedModel()
            ? Referee::onlyTrashed()->findOrFail($refereeId)
            : Referee::findOrFail($refereeId);

        return match ($lifecycleAction) {
            RosterLifecycleAction::Employ,
            RosterLifecycleAction::Release,
            RosterLifecycleAction::Retire,
            RosterLifecycleAction::Unretire,
            RosterLifecycleAction::Suspend,
            RosterLifecycleAction::Reinstate,
            RosterLifecycleAction::Injure,
            RosterLifecycleAction::ClearFromInjury,
            RosterLifecycleAction::Restore => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Referee, $referee, fn () => $action($referee)),
        };
    }
}
