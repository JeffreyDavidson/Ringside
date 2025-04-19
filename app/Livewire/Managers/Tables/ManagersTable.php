<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Actions\Managers\ClearInjuryAction;
use App\Actions\Managers\EmployAction;
use App\Actions\Managers\InjureAction;
use App\Actions\Managers\ReinstateAction;
use App\Actions\Managers\ReleaseAction;
use App\Actions\Managers\RestoreAction;
use App\Actions\Managers\RetireAction;
use App\Actions\Managers\SuspendAction;
use App\Actions\Managers\UnretireAction;
use App\Builders\ManagerBuilder;
use App\Enums\EmploymentStatus;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Exceptions\CannotBeEmployedException;
use App\Exceptions\CannotBeInjuredException;
use App\Exceptions\CannotBeReinstatedException;
use App\Exceptions\CannotBeReleasedException;
use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeSuspendedException;
use App\Exceptions\CannotBeUnretiredException;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\Manager;
use App\View\Columns\FirstEmploymentDateColumn;
use App\View\Filters\FirstEmploymentFilter;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class ManagersTable extends BaseTableWithActions
{
    use HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'managers';

    protected string $routeBasePath = 'managers';

    protected string $resourceName = 'managers';

    /**
     * @return ManagerBuilder<Manager>
     */
    public function builder(): ManagerBuilder
    {
        return Manager::query()
            ->with('firstEmployment')
            ->oldest('last_name');
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
            Column::make(__('managers.name'), 'full_name')
                ->searchable(),
            $this->getDefaultStatusColumn(),
            FirstEmploymentDateColumn::make(__('employments.started_at')),
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
        $statuses = collect(EmploymentStatus::cases())->pluck('name', 'value')->toArray();

        return [
            $this->getDefaultStatusFilter($statuses),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'managers_employments.started_at', 'managers_employments.ended_at'),
        ];
    }

    public function delete(Manager $manager): void
    {
        $this->deleteModel($manager);
    }

    /**
     * Clear an injured manager.
     */
    public function clearFromInjury(Manager $manager): RedirectResponse
    {
        Gate::authorize('clearFromInjury', $manager);

        try {
            resolve(ClearInjuryAction::class)->handle($manager);
        } catch (CannotBeClearedFromInjuryException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Employ a manager.
     */
    public function employ(Manager $manager): RedirectResponse
    {
        Gate::authorize('employ', $manager);

        try {
            resolve(EmployAction::class)->handle($manager);
        } catch (CannotBeEmployedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Injure a manager.
     */
    public function injure(Manager $manager): RedirectResponse
    {
        Gate::authorize('injure', $manager);

        try {
            resolve(InjureAction::class)->handle($manager);
        } catch (CannotBeInjuredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Reinstate a suspended manager.
     */
    public function reinstate(Manager $manager): RedirectResponse
    {
        Gate::authorize('reinstate', $manager);

        try {
            resolve(ReinstateAction::class)->handle($manager);
        } catch (CannotBeReinstatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Release a manager.
     */
    public function release(Manager $manager): RedirectResponse
    {
        Gate::authorize('release', $manager);

        try {
            resolve(ReleaseAction::class)->handle($manager);
        } catch (CannotBeReleasedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Restore a deleted manager.
     */
    public function restore(int $managerId): RedirectResponse
    {
        $manager = Manager::onlyTrashed()->findOrFail($managerId);

        Gate::authorize('restore', $manager);

        try {
            resolve(RestoreAction::class)->handle($manager);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Retire a manager.
     */
    public function retire(Manager $manager): RedirectResponse
    {
        Gate::authorize('retire', $manager);

        try {
            resolve(RetireAction::class)->handle($manager);
        } catch (CannotBeRetiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Suspend a manager.
     */
    public function suspend(Manager $manager): RedirectResponse
    {
        Gate::authorize('suspend', $manager);

        try {
            resolve(SuspendAction::class)->handle($manager);
        } catch (CannotBeSuspendedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Unretire a retired manager.
     */
    public function unretire(Manager $manager): RedirectResponse
    {
        Gate::authorize('unretire', $manager);

        try {
            resolve(UnretireAction::class)->handle($manager);
        } catch (CannotBeUnretiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }
}
