<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Actions\TagTeams\EmployAction;
use App\Actions\TagTeams\ReinstateAction;
use App\Actions\TagTeams\ReleaseAction;
use App\Actions\TagTeams\RestoreAction;
use App\Actions\TagTeams\RetireAction;
use App\Actions\TagTeams\SuspendAction;
use App\Actions\TagTeams\UnretireAction;
use App\Builders\TagTeamBuilder;
use App\Enums\EmploymentStatus;
use App\Exceptions\CannotBeEmployedException;
use App\Exceptions\CannotBeReinstatedException;
use App\Exceptions\CannotBeReleasedException;
use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeSuspendedException;
use App\Exceptions\CannotBeUnretiredException;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\TagTeam;
use App\View\Columns\FirstEmploymentDateColumn;
use App\View\Filters\FirstEmploymentFilter;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class TagTeamsTable extends BaseTableWithActions
{
    use HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'tag_teams';

    protected string $routeBasePath = 'tag-teams';

    protected string $resourceName = 'tag teams';

    public function builder(): TagTeamBuilder
    {
        return TagTeam::query()
            ->with('currentEmployment')
            ->oldest('name');
    }

    public function configure(): void
    {
        $this->addExtraWithSum('currentWrestlers', 'weight');
    }

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('tag-teams.name'), 'name')
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
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'tag_teams_employments.started_at', 'tag_teams_employments.ended_at'),
        ];
    }

    public function delete(TagTeam $tagTeam): void
    {
        $this->deleteModel($tagTeam);
    }

    /**
     * Employ a tag team.
     */
    public function employ(TagTeam $tagTeam): RedirectResponse
    {
        Gate::authorize('employ', $tagTeam);

        try {
            resolve(EmployAction::class)->handle($tagTeam);
        } catch (CannotBeEmployedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Reinstate a tag team.
     */
    public function reinstate(TagTeam $tagTeam): RedirectResponse
    {
        Gate::authorize('reinstate', $tagTeam);

        try {
            resolve(ReinstateAction::class)->handle($tagTeam);
        } catch (CannotBeReinstatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Release a tag team.
     */
    public function release(TagTeam $tagTeam): RedirectResponse
    {
        Gate::authorize('release', $tagTeam);

        try {
            resolve(ReleaseAction::class)->handle($tagTeam);
        } catch (CannotBeReleasedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Restore a deleted tag team.
     */
    public function restore(int $tagTeamId): RedirectResponse
    {
        $tagTeam = TagTeam::onlyTrashed()->findOrFail($tagTeamId);

        Gate::authorize('restore', $tagTeam);

        try {
            resolve(RestoreAction::class)->handle($tagTeam);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Retire a tag team.
     */
    public function retire(TagTeam $tagTeam): RedirectResponse
    {
        Gate::authorize('retire', $tagTeam);

        try {
            resolve(RetireAction::class)->handle($tagTeam);
        } catch (CannotBeRetiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Suspend a tag team.
     */
    public function suspend(TagTeam $tagTeam): RedirectResponse
    {
        Gate::authorize('suspend', $tagTeam);

        try {
            resolve(SuspendAction::class)->handle($tagTeam);
        } catch (CannotBeSuspendedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Unretire a tag team.
     */
    public function unretire(TagTeam $tagTeam): RedirectResponse
    {
        Gate::authorize('unretire', $tagTeam);

        try {
            resolve(UnretireAction::class)->handle($tagTeam);
        } catch (CannotBeUnretiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }
}
