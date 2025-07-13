<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Actions\Stables\DebutAction;
use App\Actions\Stables\DisbandAction;
use App\Actions\Stables\RestoreAction;
use App\Actions\Stables\RetireAction;
use App\Actions\Stables\UnretireAction;
use App\Builders\Roster\StableBuilder;
use App\Exceptions\Status\CannotBeDisbandedException;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Exceptions\Status\CannotBeUnretiredException;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Components\Tables\Columns\FirstActivityPeriodColumn;
use App\Livewire\Components\Tables\Filters\FirstActivityPeriodFilter;
use App\Models\Stables\Stable;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class StablesTable extends BaseTableWithActions
{
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
                ->label(fn ($row) => $row->status?->label() ?? 'Unknown')
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
                    /** @var StableBuilder $builder */
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
     * Disband a stable.
     */
    public function disband(Stable $stable): RedirectResponse
    {
        Gate::authorize('disband', $stable);

        try {
            resolve(DisbandAction::class)->handle($stable);
        } catch (CannotBeDisbandedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Restore a stable.
     */
    public function restore(int $stableId): RedirectResponse
    {
        $stable = Stable::onlyTrashed()->findOrFail($stableId);

        Gate::authorize('restore', $stable);

        try {
            resolve(RestoreAction::class)->handle($stable);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Retire a stable.
     */
    public function retire(Stable $stable): RedirectResponse
    {
        Gate::authorize('retire', $stable);

        try {
            resolve(RetireAction::class)->handle($stable);
        } catch (CannotBeRetiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Unretire a stable.
     */
    public function unretire(Stable $stable): RedirectResponse
    {
        Gate::authorize('unretire', $stable);

        try {
            resolve(UnretireAction::class)->handle($stable);
        } catch (CannotBeUnretiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Handle stable actions through a unified interface.
     */
    public function handleStableAction(string $action, int $stableId): void
    {
        $stable = Stable::findOrFail($stableId);

        try {
            match ($action) {
                'debut' => resolve(DebutAction::class)->handle($stable),
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
