<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Actions\Wrestlers\ClearFromInjuryAction;
use App\Actions\Wrestlers\DeleteAction;
use App\Actions\Wrestlers\EmployAction;
use App\Actions\Wrestlers\InjureAction;
use App\Actions\Wrestlers\ReinstateAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RestoreAction;
use App\Actions\Wrestlers\RetireAction;
use App\Actions\Wrestlers\SuspendAction;
use App\Actions\Wrestlers\UnretireAction;
use App\Builders\Roster\WrestlerBuilder;
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
use App\Models\Roster\Wrestlers\Wrestler;
use Closure;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Wrestler> */
class Main extends BaseTable
{
    use ExecutesBusinessActions;
    use ExecutesRosterActions;

    protected bool $showActionColumn = true;

    protected string $databaseTableName = 'wrestlers';

    protected string $routeBasePath = 'wrestlers';

    protected string $resourceName = 'wrestlers';

    /** @return WrestlerBuilder<Wrestler> */
    public function builder(): WrestlerBuilder
    {
        return Wrestler::query()
            ->withEmploymentStatusState()
            ->withFirstEmployment();
    }

    protected function configure(): void
    {
        Gate::authorize('viewAny', Wrestler::class);
    }

    /**
     * @return array<int, Column>
     **/
    public function columns(): array
    {
        return [
            Column::make(__('wrestlers.name'), 'name')
                ->searchable(),
            Column::make(__('core.status'), 'status')
                ->label(fn (Wrestler $row) => $row->status->label())
                ->excludeFromColumnSelect(),
            Column::make(__('wrestlers.height'), 'height'),
            Column::make(__('wrestlers.weight'), 'weight'),
            Column::make(__('wrestlers.hometown'), 'hometown'),
            FirstEmploymentDateColumn::make(__('employments.started_at')),
        ];
    }

    /**
     * @return array<int, Filter>
     **/
    public function filters(): array
    {
        return [
            SelectFilter::make(__('core.status'))
                ->setFilterPillTitle(__('core.status'))
                ->options(EmploymentStatus::filterOptions())
                ->filter(function (WrestlerBuilder $builder, string $value): void {
                    $status = EmploymentStatus::tryFrom($value);

                    if ($status !== null) {
                        $builder->whereEmploymentStatus($status);
                    }
                }),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'employments.started_at', 'employments.ended_at'),
        ];
    }

    public function delete(Wrestler $wrestler, DeleteAction $deleteAction): void
    {
        Gate::authorize('delete', $wrestler);

        $this->executeBusinessAction(function () use ($deleteAction, $wrestler): void {
            $deleteAction->handle($wrestler);
        }, __('wrestlers.actions.deleted'));
    }

    /**
     * Restore a deleted wrestler.
     */
    public function restore(int $wrestlerId, RestoreAction $restoreAction): void
    {
        if ($this->executeWrestlerAction(RosterLifecycleAction::Restore, $wrestlerId, fn (Wrestler $wrestler) => $restoreAction->handle($wrestler))) {
            $this->redirectRoute('wrestlers.index');
        }
    }

    /**
     * Override the default action column to use wrestler-specific actions.
     *
     * @var array<string, string>
     */
    protected $listeners = ['wrestler-action' => 'handleWrestlerAction'];

    protected function getDefaultActionColumn(): Column
    {
        return Column::make(__('core.actions'))
            ->label(fn (Wrestler $row) => view('components.tables.columns.wrestler-actions', [
                'wrestler' => $row,
            ])->render())
            ->html()
            ->excludeFromColumnSelect();
    }

    public function handleWrestlerAction(
        string $action,
        int $wrestlerId,
        ClearFromInjuryAction $clearFromInjuryAction,
        EmployAction $employAction,
        InjureAction $injureAction,
        ReinstateAction $reinstateAction,
        ReleaseAction $releaseAction,
        RestoreAction $restoreAction,
        RetireAction $retireAction,
        SuspendAction $suspendAction,
        UnretireAction $unretireAction,
    ): void {
        $lifecycleAction = RosterLifecycleAction::from($action);

        $successful = $this->executeWrestlerAction($lifecycleAction, $wrestlerId, match ($lifecycleAction) {
            RosterLifecycleAction::Employ => fn (Wrestler $wrestler) => $employAction->handle($wrestler),
            RosterLifecycleAction::Release => fn (Wrestler $wrestler) => $releaseAction->handle($wrestler),
            RosterLifecycleAction::Retire => fn (Wrestler $wrestler) => $retireAction->handle($wrestler),
            RosterLifecycleAction::Unretire => fn (Wrestler $wrestler) => $unretireAction->handle($wrestler),
            RosterLifecycleAction::Suspend => fn (Wrestler $wrestler) => $suspendAction->handle($wrestler),
            RosterLifecycleAction::Reinstate => fn (Wrestler $wrestler) => $reinstateAction->handle($wrestler),
            RosterLifecycleAction::Injure => fn (Wrestler $wrestler) => $injureAction->handle($wrestler),
            RosterLifecycleAction::ClearFromInjury => fn (Wrestler $wrestler) => $clearFromInjuryAction->handle($wrestler),
            RosterLifecycleAction::Restore => fn (Wrestler $wrestler) => $restoreAction->handle($wrestler),
        });

        if ($successful && $lifecycleAction === RosterLifecycleAction::Restore) {
            $this->redirectRoute('wrestlers.index');
        }
    }

    /** @param Closure(Wrestler): void $action */
    private function executeWrestlerAction(RosterLifecycleAction $lifecycleAction, int $wrestlerId, Closure $action): bool
    {
        $wrestler = $lifecycleAction->usesTrashedModel()
            ? Wrestler::onlyTrashed()->findOrFail($wrestlerId)
            : Wrestler::findOrFail($wrestlerId);

        return match ($lifecycleAction) {
            RosterLifecycleAction::Employ,
            RosterLifecycleAction::Release,
            RosterLifecycleAction::Retire,
            RosterLifecycleAction::Unretire,
            RosterLifecycleAction::Suspend,
            RosterLifecycleAction::Reinstate,
            RosterLifecycleAction::Injure,
            RosterLifecycleAction::ClearFromInjury,
            RosterLifecycleAction::Restore => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Wrestler, $wrestler, fn () => $action($wrestler)),
        };
    }
}
