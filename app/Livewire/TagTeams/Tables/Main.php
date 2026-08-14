<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Actions\TagTeams\DeleteAction;
use App\Actions\TagTeams\EmployAction;
use App\Actions\TagTeams\ReinstateAction;
use App\Actions\TagTeams\ReleaseAction;
use App\Actions\TagTeams\RestoreAction;
use App\Actions\TagTeams\RetireAction;
use App\Actions\TagTeams\SuspendAction;
use App\Actions\TagTeams\UnretireAction;
use App\Builders\Roster\TagTeamBuilder;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\Roster\TagTeams\CannotBeEmployedException;
use App\Exceptions\Roster\TagTeams\CannotBeReinstatedException;
use App\Exceptions\Roster\TagTeams\CannotBeReleasedException;
use App\Exceptions\Roster\TagTeams\CannotBeRestoredException;
use App\Exceptions\Roster\TagTeams\CannotBeRetiredException;
use App\Exceptions\Roster\TagTeams\CannotBeSuspendedException;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\TagTeams\TagTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class Main extends BaseTable
{
    protected bool $showActionColumn = true;

    protected string $databaseTableName = 'tag_teams';

    protected string $routeBasePath = 'tag-teams';

    protected string $resourceName = 'tag teams';

    /** @return TagTeamBuilder<TagTeam> */
    public function builder(): TagTeamBuilder
    {
        return TagTeam::query()
            ->with('currentEmployment')
            ->oldest('name');
    }

    public function configure(): void
    {
        Gate::authorize('viewList', TagTeam::class);
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
            Column::make(__('core.status'), 'status')
                ->label(fn (TagTeam $row) => $row->status->label())
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
                ->filter(function (TagTeamBuilder $builder, string $value) {
                    /** @var TagTeamBuilder<TagTeam> $builder */
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

    public function delete(TagTeam $tagTeam): void
    {
        Gate::authorize('delete', $tagTeam);

        resolve(DeleteAction::class)->handle($tagTeam);
        session()->flash('status', 'Tag team successfully deleted.');
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
        } catch (CannotBeRestoredException $e) {
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

    /**
     * Handle tag team actions through a unified interface.
     */
    public function handleTagTeamAction(string $action, int $tagTeamId): void
    {
        $tagTeam = TagTeam::findOrFail($tagTeamId);

        try {
            match ($action) {
                'employ' => resolve(EmployAction::class)->handle($tagTeam),
                'release' => resolve(ReleaseAction::class)->handle($tagTeam),
                'suspend' => resolve(SuspendAction::class)->handle($tagTeam),
                'reinstate' => resolve(ReinstateAction::class)->handle($tagTeam),
                'retire' => resolve(RetireAction::class)->handle($tagTeam),
                'unretire' => resolve(UnretireAction::class)->handle($tagTeam),
                default => null,
            };
        } catch (BaseBusinessException $e) {
            session()->flash('error', $e->getMessage());
        }
    }
}
