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
use App\Enums\Roster\RosterEntityType;
use App\Enums\Roster\RosterLifecycleAction;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/** @extends BaseTable<TagTeam> */
class Main extends BaseTable
{
    use ExecutesRosterActions;

    protected bool $showActionColumn = true;

    protected string $databaseTableName = 'tag_teams';

    protected string $routeBasePath = 'tag-teams';

    protected string $resourceName = 'tag teams';

    /** @return TagTeamBuilder<TagTeam> */
    public function builder(): TagTeamBuilder
    {
        return TagTeam::query()
            ->withEmploymentStatusState()
            ->with('firstEmployment')
            ->oldest('name');
    }

    public function configure(): void
    {
        Gate::authorize('viewAny', TagTeam::class);
    }

    /**
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
                ->filter(function (TagTeamBuilder $builder, string $value): void {
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

    public function employ(TagTeam $tagTeam): void
    {
        $this->executeTagTeamAction(RosterLifecycleAction::Employ, $tagTeam->id);
    }

    public function reinstate(TagTeam $tagTeam): void
    {
        $this->executeTagTeamAction(RosterLifecycleAction::Reinstate, $tagTeam->id);
    }

    public function release(TagTeam $tagTeam): void
    {
        $this->executeTagTeamAction(RosterLifecycleAction::Release, $tagTeam->id);
    }

    public function restore(int $tagTeamId): void
    {
        $this->executeTagTeamAction(RosterLifecycleAction::Restore, $tagTeamId);
    }

    public function retire(TagTeam $tagTeam): void
    {
        $this->executeTagTeamAction(RosterLifecycleAction::Retire, $tagTeam->id);
    }

    public function suspend(TagTeam $tagTeam): void
    {
        $this->executeTagTeamAction(RosterLifecycleAction::Suspend, $tagTeam->id);
    }

    public function unretire(TagTeam $tagTeam): void
    {
        $this->executeTagTeamAction(RosterLifecycleAction::Unretire, $tagTeam->id);
    }

    private function executeTagTeamAction(RosterLifecycleAction $lifecycleAction, int $tagTeamId): void
    {
        $tagTeam = $lifecycleAction === RosterLifecycleAction::Restore
            ? TagTeam::onlyTrashed()->findOrFail($tagTeamId)
            : TagTeam::query()->findOrFail($tagTeamId);

        match ($lifecycleAction) {
            RosterLifecycleAction::Employ => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::TagTeam, $tagTeam, fn () => resolve(EmployAction::class)->handle($tagTeam)),
            RosterLifecycleAction::Release => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::TagTeam, $tagTeam, fn () => resolve(ReleaseAction::class)->handle($tagTeam)),
            RosterLifecycleAction::Suspend => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::TagTeam, $tagTeam, fn () => resolve(SuspendAction::class)->handle($tagTeam)),
            RosterLifecycleAction::Reinstate => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::TagTeam, $tagTeam, fn () => resolve(ReinstateAction::class)->handle($tagTeam)),
            RosterLifecycleAction::Retire => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::TagTeam, $tagTeam, fn () => resolve(RetireAction::class)->handle($tagTeam)),
            RosterLifecycleAction::Unretire => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::TagTeam, $tagTeam, fn () => resolve(UnretireAction::class)->handle($tagTeam)),
            RosterLifecycleAction::Restore => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::TagTeam, $tagTeam, fn () => resolve(RestoreAction::class)->handle($tagTeam)),
            RosterLifecycleAction::ClearFromInjury, RosterLifecycleAction::Injure => throw new InvalidArgumentException("{$lifecycleAction->value} is not a tag team lifecycle action."),
        };
    }
}
