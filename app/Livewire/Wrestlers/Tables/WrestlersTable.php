<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Actions\Wrestlers\ClearInjuryAction;
use App\Actions\Wrestlers\EmployAction;
use App\Actions\Wrestlers\InjureAction;
use App\Actions\Wrestlers\ReinstateAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RestoreAction;
use App\Actions\Wrestlers\RetireAction;
use App\Actions\Wrestlers\SuspendAction;
use App\Actions\Wrestlers\UnretireAction;
use App\Builders\WrestlerBuilder;
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
use App\Models\Wrestler;
use App\View\Columns\FirstEmploymentDateColumn;
use App\View\Filters\FirstEmploymentFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class WrestlersTable extends BaseTableWithActions
{
    use HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'wrestlers';

    protected string $routeBasePath = 'wrestlers';

    protected string $resourceName = 'wrestlers';

    public function builder(): WrestlerBuilder
    {
        return Wrestler::query()
            ->with('currentEmployment');
    }

    public function configure(): void {}

    /**
     * @return array<int, Column>
     **/
    public function columns(): array
    {
        return [
            Column::make(__('wrestlers.name'), 'name')
                ->searchable(),
            $this->getDefaultStatusColumn(),
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
        /** @var array<string, string> $statuses */
        $statuses = collect(EmploymentStatus::cases())->pluck('name', 'value')->toArray();

        return [
            $this->getDefaultStatusFilter($statuses),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'wrestlers_employments.started_at', 'wrestlers_employments.ended_at'),
        ];
    }

    public function delete(Wrestler $wrestler): void
    {
        $this->deleteModel($wrestler);
    }

    /**
     * Have a wrestler heal from an injury.
     */
    public function healFromInjury(Wrestler $wrestler): RedirectResponse
    {
        Gate::authorize('clearFromInjury', $wrestler);

        try {
            resolve(ClearInjuryAction::class)->handle($wrestler);
        } catch (CannotBeClearedFromInjuryException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Employ a wrestler.
     */
    public function employ(Wrestler $wrestler): RedirectResponse
    {
        Gate::authorize('employ', $wrestler);

        try {
            resolve(EmployAction::class)->handle($wrestler);
        } catch (CannotBeEmployedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Injure a wrestler.
     */
    public function injure(Wrestler $wrestler): RedirectResponse
    {
        Gate::authorize('injure', $wrestler);

        try {
            resolve(InjureAction::class)->handle($wrestler);
        } catch (CannotBeInjuredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Reinstate a wrestler.
     */
    public function reinstate(Wrestler $wrestler): RedirectResponse
    {
        Gate::authorize('reinstate', $wrestler);

        try {
            resolve(ReinstateAction::class)->handle($wrestler);
        } catch (CannotBeReinstatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('wrestlers.index');
    }

    /**
     * Release a wrestler.
     */
    public function release(Wrestler $wrestler): RedirectResponse
    {
        Gate::authorize('release', $wrestler);

        try {
            resolve(ReleaseAction::class)->handle($wrestler);
        } catch (CannotBeReleasedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
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
     * Retire a wrestler.
     */
    public function retire(Wrestler $wrestler): RedirectResponse
    {
        Gate::authorize('retire', $wrestler);

        try {
            resolve(RetireAction::class)->handle($wrestler);
        } catch (CannotBeRetiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Suspend a wrestler.
     */
    public function suspend(Wrestler $wrestler): RedirectResponse
    {
        Gate::authorize('suspend', $wrestler);

        try {
            resolve(SuspendAction::class)->handle($wrestler);
        } catch (CannotBeSuspendedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Unretire a wrestler.
     */
    public function unretire(Wrestler $wrestler): RedirectResponse
    {
        Gate::authorize('unretire', $wrestler);

        try {
            resolve(UnretireAction::class)->handle($wrestler);
        } catch (CannotBeUnretiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }
}
