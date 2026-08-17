<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Actions\Wrestlers\DeleteAction;
use App\Actions\Wrestlers\EmployAction;
use App\Actions\Wrestlers\HealAction;
use App\Actions\Wrestlers\InjureAction;
use App\Actions\Wrestlers\ReinstateAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RestoreAction;
use App\Actions\Wrestlers\RetireAction;
use App\Actions\Wrestlers\SuspendAction;
use App\Actions\Wrestlers\UnretireAction;
use App\Builders\Roster\WrestlerBuilder;
use App\Enums\Roster\RosterEntityType;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\Wrestlers\Wrestler;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Wrestler> */
class Main extends BaseTable
{
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
            ->with('firstEmployment');
    }

    public function configure(): void
    {
        Gate::authorize('viewList', Wrestler::class);
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

        resolve(DeleteAction::class)->handle($wrestler);
        session()->flash('status', 'Wrestler successfully deleted.');
    }

    /**
     * Restore a deleted wrestler.
     */
    public function restore(int $wrestlerId): RedirectResponse
    {
        $wrestler = Wrestler::onlyTrashed()->findOrFail($wrestlerId);

        Gate::authorize('restore', $wrestler);

        resolve(RestoreAction::class)->handle($wrestler);

        return back();
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
        $wrestler = $action === 'restore'
            ? Wrestler::onlyTrashed()->findOrFail($wrestlerId)
            : Wrestler::findOrFail($wrestlerId);

        match ($action) {
            'employ' => $this->executeWrestlerAction('employ', 'employed', $wrestler, fn () => resolve(EmployAction::class)->handle($wrestler)),
            'release' => $this->executeWrestlerAction('release', 'released', $wrestler, fn () => resolve(ReleaseAction::class)->handle($wrestler)),
            'retire' => $this->executeWrestlerAction('retire', 'retired', $wrestler, fn () => resolve(RetireAction::class)->handle($wrestler)),
            'unretire' => $this->executeWrestlerAction('unretire', 'unretired', $wrestler, fn () => resolve(UnretireAction::class)->handle($wrestler)),
            'suspend' => $this->executeWrestlerAction('suspend', 'suspended', $wrestler, fn () => resolve(SuspendAction::class)->handle($wrestler)),
            'reinstate' => $this->executeWrestlerAction('reinstate', 'reinstated', $wrestler, fn () => resolve(ReinstateAction::class)->handle($wrestler)),
            'injure' => $this->executeWrestlerAction('injure', 'injured', $wrestler, fn () => resolve(InjureAction::class)->handle($wrestler)),
            'heal' => $this->executeWrestlerAction('clearFromInjury', 'healed', $wrestler, fn () => resolve(HealAction::class)->handle($wrestler)),
            'restore' => $this->restore($wrestlerId),
            default => null,
        };
    }

    /** @param Closure(): void $action */
    private function executeWrestlerAction(string $ability, string $successAction, Wrestler $wrestler, Closure $action): void
    {
        Gate::authorize($ability, $wrestler);

        $this->executeRosterAction($successAction, RosterEntityType::Wrestler, $action);
    }
}
