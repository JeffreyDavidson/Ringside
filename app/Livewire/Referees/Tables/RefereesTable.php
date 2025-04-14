<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Tables;

use App\Actions\Referees\ClearInjuryAction;
use App\Actions\Referees\EmployAction;
use App\Actions\Referees\InjureAction;
use App\Actions\Referees\ReinstateAction;
use App\Actions\Referees\ReleaseAction;
use App\Actions\Referees\RestoreAction;
use App\Actions\Referees\RetireAction;
use App\Actions\Referees\SuspendAction;
use App\Actions\Referees\UnretireAction;
use App\Builders\RefereeBuilder;
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
use App\Models\Referee;
use App\View\Columns\FirstEmploymentDateColumn;
use App\View\Filters\FirstEmploymentFilter;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

final class RefereesTable extends BaseTableWithActions
{
    use HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'referees';

    protected string $routeBasePath = 'referees';

    protected string $resourceName = 'referees';

    /**
     * @return RefereeBuilder<Referee>
     */
    public function builder(): RefereeBuilder
    {
        return Referee::query()
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
            Column::make(__('referees.name'), 'full_name')
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
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'referees_employments.started_at', 'referees_employments.ended_at'),
        ];
    }

    public function delete(Referee $referee): void
    {
        $this->deleteModel($referee);
    }

    /**
     * Clear a referee.
     */
    public function clearFromInjury(Referee $referee): RedirectResponse
    {
        Gate::authorize('clearFromInjury', $referee);

        try {
            resolve(ClearInjuryAction::class)->handle($referee);
        } catch (CannotBeClearedFromInjuryException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Employ a referee.
     */
    public function employ(Referee $referee): RedirectResponse
    {
        Gate::authorize('employ', $referee);

        try {
            resolve(EmployAction::class)->handle($referee);
        } catch (CannotBeEmployedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Injure a referee.
     */
    public function injure(Referee $referee): RedirectResponse
    {
        Gate::authorize('injure', $referee);

        try {
            resolve(InjureAction::class)->handle($referee);
        } catch (CannotBeInjuredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Reinstate a referee.
     */
    public function reinstate(Referee $referee): RedirectResponse
    {
        Gate::authorize('reinstate', $referee);

        try {
            resolve(ReinstateAction::class)->handle($referee);
        } catch (CannotBeReinstatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Release a referee.
     */
    public function release(Referee $referee): RedirectResponse
    {
        Gate::authorize('release', $referee);

        try {
            resolve(ReleaseAction::class)->handle($referee);
        } catch (CannotBeReleasedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Retire a referee.
     */
    public function retire(Referee $referee): RedirectResponse
    {
        Gate::authorize('retire', $referee);

        try {
            resolve(RetireAction::class)->handle($referee);
        } catch (CannotBeRetiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Restore a deleted referee.
     */
    public function restore(int $refereeId): RedirectResponse
    {
        $referee = Referee::onlyTrashed()->findOrFail($refereeId);

        Gate::authorize('restore', Referee::class);

        try {
            resolve(RestoreAction::class)->handle($referee);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Suspend a referee.
     */
    public function suspend(Referee $referee): RedirectResponse
    {
        Gate::authorize('suspend', $referee);

        try {
            resolve(SuspendAction::class)->handle($referee);
        } catch (CannotBeSuspendedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Unretire a referee.
     */
    public function unretire(Referee $referee): RedirectResponse
    {
        Gate::authorize('unretire', $referee);

        try {
            resolve(UnretireAction::class)->handle($referee);
        } catch (CannotBeUnretiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }
}
