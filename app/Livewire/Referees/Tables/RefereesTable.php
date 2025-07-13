<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Tables;

use App\Actions\Referees\EmployAction;
use App\Actions\Referees\HealAction;
use App\Actions\Referees\InjureAction;
use App\Actions\Referees\ReinstateAction;
use App\Actions\Referees\ReleaseAction;
use App\Actions\Referees\RestoreAction;
use App\Actions\Referees\RetireAction;
use App\Actions\Referees\SuspendAction;
use App\Actions\Referees\UnretireAction;
use App\Builders\Roster\RefereeBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Status\CannotBeClearedFromInjuryException;
use App\Exceptions\Status\CannotBeEmployedException;
use App\Exceptions\Status\CannotBeInjuredException;
use App\Exceptions\Status\CannotBeReinstatedException;
use App\Exceptions\Status\CannotBeReleasedException;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Exceptions\Status\CannotBeSuspendedException;
use App\Exceptions\Status\CannotBeUnretiredException;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Referees\Components\RefereeActionsComponent;
use App\Models\Referees\Referee;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class RefereesTable extends BaseTableWithActions
{
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

    public function configure(): void
    {
        Gate::authorize('viewList', Referee::class);
    }

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
            Column::make(__('core.status'), 'status')
                ->label(fn ($row) => $row->status?->label() ?? 'Unknown')
                ->excludeFromColumnSelect(),
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
        return [
            SelectFilter::make(__('core.status')) // @phpstan-ignore-line method.notFound
                ->setFilterPillTitle(__('core.status'))
                ->options([
                    '' => __('core.all'),
                    'employed' => 'Employed',
                    'future_employment' => 'Awaiting Employment',
                    'released' => 'Released',
                    'unemployed' => 'Unemployed',
                    'retired' => 'Retired',
                ])
                ->filter(function ($builder, string $value) {
                    /** @var RefereeBuilder $builder */
                    match ($value) {
                        'employed' => $builder->employed(),
                        'future_employment' => $builder->where('status', EmploymentStatus::FutureEmployment),
                        'released' => $builder->released(),
                        'unemployed' => $builder->unemployed(),
                        'retired' => $builder->retired(),
                        default => null,
                    };
                }),
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
            resolve(HealAction::class)->handle($referee);
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

    public function handleRefereeAction(string $action, int $refereeId): void
    {
        $referee = Referee::findOrFail($refereeId);

        // Delegate to the RefereeActionsComponent
        $actionsComponent = new RefereeActionsComponent();
        $actionsComponent->referee = $referee;

        match ($action) {
            'employ' => $actionsComponent->employ(),
            'release' => $actionsComponent->release(),
            'retire' => $actionsComponent->retire(),
            'unretire' => $actionsComponent->unretire(),
            'suspend' => $actionsComponent->suspend(),
            'reinstate' => $actionsComponent->reinstate(),
            'injure' => $actionsComponent->injure(),
            'heal' => $actionsComponent->healFromInjury(),
            'restore' => $actionsComponent->restore(),
            default => null,
        };
    }
}
