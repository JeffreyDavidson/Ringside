<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Actions\TagTeams\DeleteAction;
use App\Actions\TagTeams\RestoreAction;
use App\Builders\Roster\IndividualBuilder;
use App\Builders\Roster\TagTeamBuilder;
use App\Enums\Roster\RosterEntityType;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Collection;

/** @extends BaseTable<TagTeam> */
class Main extends BaseTable
{
    #[\Override]
    protected bool $showActionColumn = true;

    #[\Override]
    protected string $modelClass = TagTeam::class;

    #[\Override]
    protected ?string $indexView = 'livewire.tag-teams.tables.main';

    #[\Override]
    protected string $actionsView = 'components.tables.columns.tag-team-actions';

    #[\Override]
    protected string $actionsRowVariable = 'tagTeam';

    #[\Override]
    protected string $databaseTableName = 'tag_teams';

    #[\Override]
    protected string $routeBasePath = 'tag-teams';

    #[\Override]
    protected string $resourceName = 'tag teams';

    /** @return TagTeamBuilder<TagTeam> */
    public function builder(): TagTeamBuilder
    {
        return TagTeam::query()
            ->withFirstEmployment()
            ->with('currentWrestlers')
            ->oldest('name')
            ->orderBy('tag_teams.id');
    }

    #[\Override]
    protected function projectRowState(Collection $rows): void
    {
        $rows->loadExists(TagTeamBuilder::ROSTER_STATE);
        new Collection($rows->flatMap(fn (TagTeam $tagTeam): Collection => $tagTeam->currentWrestlers)->all())->loadExists(IndividualBuilder::AVAILABILITY_STATE);
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
    #[\Override]
    public function filters(): array
    {
        return [
            SelectFilter::make(__('core.status'), 'status')
                ->options($this->statusOptionsWithDeleted(EmploymentStatus::filterOptions()))
                ->filter(function (TagTeamBuilder $builder, string $value): void {
                    /** @var TagTeamBuilder<TagTeam> $builder */
                    if ($this->isDeletedFilterValue($value)) {
                        $builder->onlyTrashed();

                        return;
                    }

                    $status = EmploymentStatus::tryFrom($value);

                    if ($status !== null) {
                        $builder->whereEmploymentStatus($status);
                    }
                }),
            FirstEmploymentFilter::make(__('core.employment_date'), 'employment_date')->setFields('employments', 'employments.started_at', 'employments.ended_at'),
        ];
    }

    public function delete(TagTeam $tagTeam, DeleteAction $deleteAction): void
    {
        $this->deleteRecord($tagTeam, $deleteAction->handle(...), __('tag-teams.actions.deleted'));
    }

    public function restore(int $tagTeamId, RestoreAction $restoreAction): void
    {
        $this->restoreRecord(TagTeam::withTrashed()->findOrFail($tagTeamId), $restoreAction->handle(...), __('tag-teams.actions.restored'), RosterEntityType::TagTeam);
    }
}
