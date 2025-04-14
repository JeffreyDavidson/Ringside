<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Actions\Stables\ActivateAction;
use App\Actions\Stables\DeactivateAction;
use App\Actions\Stables\RestoreAction;
use App\Actions\Stables\RetireAction;
use App\Actions\Stables\UnretireAction;
use App\Builders\StableBuilder;
use App\Enums\ActivationStatus;
use App\Exceptions\CannotBeActivatedException;
use App\Exceptions\CannotBeDeactivatedException;
use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeUnretiredException;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\Stable;
use App\View\Columns\FirstActivationDateColumn;
use App\View\Filters\FirstActivationFilter;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

final class StablesTable extends BaseTableWithActions
{
    use HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'stables';

    protected string $routeBasePath = 'stables';

    protected string $resourceName = 'stables';

    public function builder(): StableBuilder
    {
        return Stable::query()
            ->with('currentActivation')
            ->oldest('name');
    }

    public function configure(): void {}

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
            $this->getDefaultStatusColumn(),
            FirstActivationDateColumn::make(__('activations.started_at')),
        ];
    }

    /**
     * Undocumented function
     *
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        /** @var array<string, string> $statuses */
        $statuses = collect(ActivationStatus::cases())->pluck('name', 'value')->toArray();

        return [
            $this->getDefaultStatusFilter($statuses),
            FirstActivationFilter::make('Activation Date')->setFields('activations', 'stables_activations.started_at', 'stables_activations.ended_at'),
        ];
    }

    public function delete(Stable $stable): void
    {
        $this->deleteModel($stable);
    }

    /**
     * Activate a stable.
     */
    public function activate(Stable $stable): RedirectResponse
    {
        Gate::authorize('activate', $stable);

        try {
            resolve(ActivateAction::class)->handle($stable);
        } catch (CannotBeActivatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Deactivate a stable.
     */
    public function deactivate(Stable $stable): RedirectResponse
    {
        Gate::authorize('deactivate', $stable);

        try {
            resolve(DeactivateAction::class)->handle($stable);
        } catch (CannotBeDeactivatedException $e) {
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
}
