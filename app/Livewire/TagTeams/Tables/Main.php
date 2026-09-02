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
use App\Livewire\Concerns\ExecutesBusinessActions;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\TagTeams\TagTeam;
use Closure;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/** @extends BaseTable<TagTeam> */
class Main extends BaseTable
{
    use ExecutesBusinessActions;
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
            ->withFirstEmployment()
            ->oldest('name');
    }

    protected function configure(): void
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
                    $status = EmploymentStatus::tryFrom($value);

                    if ($status !== null) {
                        $builder->whereEmploymentStatus($status);
                    }
                }),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'employments.started_at', 'employments.ended_at'),
        ];
    }

    public function delete(TagTeam $tagTeam, DeleteAction $deleteAction): void
    {
        Gate::authorize('delete', $tagTeam);

        $this->executeBusinessAction(function () use ($deleteAction, $tagTeam): void {
            $deleteAction->handle($tagTeam);
        }, __('tag-teams.actions.deleted'));
    }

    public function employ(TagTeam $tagTeam, EmployAction $employAction): void
    {
        $this->executeTagTeamAction(RosterLifecycleAction::Employ, $tagTeam->id, fn (TagTeam $tagTeam) => $employAction->handle($tagTeam));
    }

    public function reinstate(TagTeam $tagTeam, ReinstateAction $reinstateAction): void
    {
        $this->executeTagTeamAction(RosterLifecycleAction::Reinstate, $tagTeam->id, fn (TagTeam $tagTeam) => $reinstateAction->handle($tagTeam));
    }

    public function release(TagTeam $tagTeam, ReleaseAction $releaseAction): void
    {
        $this->executeTagTeamAction(RosterLifecycleAction::Release, $tagTeam->id, fn (TagTeam $tagTeam) => $releaseAction->handle($tagTeam));
    }

    public function restore(int $tagTeamId, RestoreAction $restoreAction): void
    {
        if ($this->executeTagTeamAction(RosterLifecycleAction::Restore, $tagTeamId, fn (TagTeam $tagTeam) => $restoreAction->handle($tagTeam))) {
            $this->redirectRoute('tag-teams.index');
        }
    }

    public function retire(TagTeam $tagTeam, RetireAction $retireAction): void
    {
        $this->executeTagTeamAction(RosterLifecycleAction::Retire, $tagTeam->id, fn (TagTeam $tagTeam) => $retireAction->handle($tagTeam));
    }

    public function suspend(TagTeam $tagTeam, SuspendAction $suspendAction): void
    {
        $this->executeTagTeamAction(RosterLifecycleAction::Suspend, $tagTeam->id, fn (TagTeam $tagTeam) => $suspendAction->handle($tagTeam));
    }

    public function unretire(TagTeam $tagTeam, UnretireAction $unretireAction): void
    {
        $this->executeTagTeamAction(RosterLifecycleAction::Unretire, $tagTeam->id, fn (TagTeam $tagTeam) => $unretireAction->handle($tagTeam));
    }

    /** @param Closure(TagTeam): void $action */
    private function executeTagTeamAction(RosterLifecycleAction $lifecycleAction, int $tagTeamId, Closure $action): bool
    {
        $tagTeam = $lifecycleAction->usesTrashedModel()
            ? TagTeam::onlyTrashed()->findOrFail($tagTeamId)
            : TagTeam::query()->findOrFail($tagTeamId);

        return match ($lifecycleAction) {
            RosterLifecycleAction::Employ,
            RosterLifecycleAction::Release,
            RosterLifecycleAction::Suspend,
            RosterLifecycleAction::Reinstate,
            RosterLifecycleAction::Retire,
            RosterLifecycleAction::Unretire,
            RosterLifecycleAction::Restore => $this->executeAuthorizedRosterAction($lifecycleAction, RosterEntityType::TagTeam, $tagTeam, fn () => $action($tagTeam)),
            default => throw new InvalidArgumentException("{$lifecycleAction->value} is not a tag team lifecycle action."),
        };
    }
}
