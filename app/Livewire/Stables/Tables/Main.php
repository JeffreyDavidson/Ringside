<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Actions\Stables\DisbandAction;
use App\Actions\Stables\EstablishAction;
use App\Actions\Stables\RestoreAction;
use App\Actions\Stables\RetireAction;
use App\Actions\Stables\UnretireAction;
use App\Builders\Roster\StableBuilder;
use App\Exceptions\Roster\Stables\CannotBeDisbandedException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Exceptions\Roster\Stables\CannotBeRetiredException;
use App\Exceptions\Roster\Stables\CannotBeUnretiredException;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstActivityPeriodColumn;
use App\Livewire\Components\Tables\Filters\FirstActivityPeriodFilter;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Stables\Stable;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class Main extends BaseTable
{
    protected bool $showActionColumn = true;

    protected string $databaseTableName = 'stables';

    protected string $routeBasePath = 'stables';

    protected string $resourceName = 'stables';

    /** @return StableBuilder<Stable> */
    public function builder(): StableBuilder
    {
        return Stable::query()
            ->with('currentActivityPeriod')
            ->oldest('name');
    }

    public function configure(): void
    {
        Gate::authorize('viewList', Stable::class);
    }

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('stables.name'), 'name')
                ->searchable(),
            Column::make(__('core.status'), 'status')
                ->label(fn (Stable $row) => $row->status->label())
                ->excludeFromColumnSelect(),
            FirstActivityPeriodColumn::make(__('activations.started_at')),
        ];
    }

    /**
     * Undocumented function
     *
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        return [
            SelectFilter::make('Status', 'status')
                ->options([
                    '' => 'All',
                    'unestablished' => 'Unestablished',
                    'established' => 'Established',
                    'disbanded' => 'Disbanded',
                    'with_future_establishment' => 'Pending Establishment',
                ])
                ->filter(function (Builder $builder, string $value): void {
                    /** @var StableBuilder<Stable> $builder */
                    match ($value) {
                        'unestablished' => $builder->unestablished(),
                        'established' => $builder->established(),
                        'disbanded' => $builder->disbanded(),
                        'with_future_establishment' => $builder->withFutureEstablishment(),
                        default => null,
                    };
                }),
            FirstActivityPeriodFilter::make('Activation Date')->setFields('activations', 'stables_activations.started_at', 'stables_activations.ended_at'),
        ];
    }

    public function delete(Stable $stable): void
    {
        $this->deleteModel($stable);
    }

    /**
     * Establish a stable.
     */
    public function establish(Stable $stable): void
    {
        Gate::authorize('establish', $stable);

        try {
            resolve(EstablishAction::class)->handle($stable);
            $this->redirect(request()->header('Referer') ?: route('stables.index'));
        } catch (CannotBeEstablishedException $e) {
            session()->flash('error', $e->getMessage());
            $this->redirect(request()->header('Referer') ?: route('stables.index'));
        }
    }

    /**
     * Disband a stable.
     */
    public function disband(Stable $stable): void
    {
        Gate::authorize('disband', $stable);

        try {
            resolve(DisbandAction::class)->handle($stable);
            $this->redirect(request()->header('Referer') ?: route('stables.index'));
        } catch (CannotBeDisbandedException $e) {
            session()->flash('error', $e->getMessage());
            $this->redirect(request()->header('Referer') ?: route('stables.index'));
        }
    }

    /**
     * Restore a stable.
     */
    public function restore(int $stableId): void
    {
        $stable = Stable::onlyTrashed()->findOrFail($stableId);

        Gate::authorize('restore', $stable);

        try {
            resolve(RestoreAction::class)->handle($stable);
            $this->redirect(request()->header('Referer') ?: route('stables.index'));
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->redirect(request()->header('Referer') ?: route('stables.index'));
        }
    }

    /**
     * Retire a stable.
     */
    public function retire(Stable $stable): void
    {
        Gate::authorize('retire', $stable);

        try {
            resolve(RetireAction::class)->handle($stable);
            $this->redirect(request()->header('Referer') ?: route('stables.index'));
        } catch (CannotBeRetiredException $e) {
            session()->flash('error', $e->getMessage());
            $this->redirect(request()->header('Referer') ?: route('stables.index'));
        }
    }

    /**
     * Unretire a stable.
     */
    public function unretire(Stable $stable): void
    {
        Gate::authorize('unretire', $stable);

        try {
            resolve(UnretireAction::class)->handle($stable);
            $this->redirect(request()->header('Referer') ?: route('stables.index'));
        } catch (CannotBeUnretiredException $e) {
            session()->flash('error', $e->getMessage());
            $this->redirect(request()->header('Referer') ?: route('stables.index'));
        }
    }

    /**
     * Handle stable actions through a unified interface.
     */
    public function handleStableAction(string $action, int $stableId): void
    {
        $stable = Stable::findOrFail($stableId);

        try {
            match ($action) {
                'establish' => resolve(EstablishAction::class)->handle($stable),
                'disband' => resolve(DisbandAction::class)->handle($stable),
                'retire' => resolve(RetireAction::class)->handle($stable),
                'unretire' => resolve(UnretireAction::class)->handle($stable),
                default => null,
            };
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
}
