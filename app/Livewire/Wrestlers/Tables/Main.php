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
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Livewire\Concerns\ExecutesSoftDeleteActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Wrestler> */
class Main extends BaseTable
{
    use ExecutesRosterActions;
    use ExecutesSoftDeleteActions;

    protected bool $showActionColumn = true;

    protected string $databaseTableName = 'wrestlers';

    protected string $routeBasePath = 'wrestlers';

    protected string $resourceName = 'wrestlers';

    /** @return WrestlerBuilder<Wrestler> */
    public function builder(): WrestlerBuilder
    {
        return Wrestler::query()
            ->withEmploymentStatusState()
            ->with('firstEmployment');
    }

    public function configure(): void
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
                ->options([
                    '' => __('core.all'),
                    'employed' => 'Employed',
                    'future_employment' => 'Awaiting Employment',
                    'released' => 'Released',
                    'unemployed' => 'Unemployed',
                    'retired' => 'Retired',
                ])
                ->filter(function (WrestlerBuilder $builder, string $value): void {
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

    public function delete(Wrestler $wrestler): void
    {
        Gate::authorize('delete', $wrestler);

        $this->executeSoftDeleteAction(function () use ($wrestler): void {
            resolve(DeleteAction::class)->handle($wrestler);
        }, 'Wrestler successfully deleted.');
    }

    /**
     * Restore a deleted wrestler.
     */
    public function restore(int $wrestlerId): void
    {
        $wrestler = Wrestler::onlyTrashed()->findOrFail($wrestlerId);

        Gate::authorize('restore', $wrestler);

        resolve(RestoreAction::class)->handle($wrestler);

        $this->redirectRoute('wrestlers.index');
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

    public function handleWrestlerAction(string $action, int $wrestlerId): void
    {
        $lifecycleAction = RosterLifecycleAction::from($action);
        $wrestler = $lifecycleAction->usesTrashedModel()
            ? Wrestler::onlyTrashed()->findOrFail($wrestlerId)
            : Wrestler::findOrFail($wrestlerId);

        match ($lifecycleAction) {
            RosterLifecycleAction::Employ => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Wrestler, $wrestler, fn () => resolve(EmployAction::class)->handle($wrestler)),
            RosterLifecycleAction::Release => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Wrestler, $wrestler, fn () => resolve(ReleaseAction::class)->handle($wrestler)),
            RosterLifecycleAction::Retire => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Wrestler, $wrestler, fn () => resolve(RetireAction::class)->handle($wrestler)),
            RosterLifecycleAction::Unretire => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Wrestler, $wrestler, fn () => resolve(UnretireAction::class)->handle($wrestler)),
            RosterLifecycleAction::Suspend => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Wrestler, $wrestler, fn () => resolve(SuspendAction::class)->handle($wrestler)),
            RosterLifecycleAction::Reinstate => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Wrestler, $wrestler, fn () => resolve(ReinstateAction::class)->handle($wrestler)),
            RosterLifecycleAction::Injure => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Wrestler, $wrestler, fn () => resolve(InjureAction::class)->handle($wrestler)),
            RosterLifecycleAction::ClearFromInjury => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::Wrestler, $wrestler, fn () => resolve(ClearFromInjuryAction::class)->handle($wrestler)),
            RosterLifecycleAction::Restore => $this->restore($wrestlerId),
        };
    }
}
