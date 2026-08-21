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
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Livewire\Concerns\ExecutesSoftDeleteActions;
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
    use ExecutesSoftDeleteActions;

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
                ->options(EmploymentStatus::filterOptions())
                ->filter(function (TagTeamBuilder $builder, string $value): void {
                    /** @var TagTeamBuilder<TagTeam> $builder */
                    match (EmploymentStatus::tryFrom($value)) {
                        EmploymentStatus::Employed => $builder->employed(),
                        EmploymentStatus::FutureEmployment => $builder->futureEmployed(),
                        EmploymentStatus::Released => $builder->released(),
                        EmploymentStatus::Unemployed => $builder->unemployed(),
                        EmploymentStatus::Retired => $builder->retired(),
                        default => null,
                    };
                }),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'employments.started_at', 'employments.ended_at'),
        ];
    }

    public function delete(TagTeam $tagTeam): void
    {
        Gate::authorize('delete', $tagTeam);

        $this->executeSoftDeleteAction(function () use ($tagTeam): void {
            resolve(DeleteAction::class)->handle($tagTeam);
        }, 'Tag team successfully deleted.');
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
        $tagTeam = $this->findRosterModel($lifecycleAction, TagTeam::class, $tagTeamId);

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
