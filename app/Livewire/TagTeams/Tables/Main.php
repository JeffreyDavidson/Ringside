<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Actions\TagTeams\DeleteAction;
use App\Builders\Roster\TagTeamBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstEmploymentDateColumn;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Concerns\ExecutesBusinessActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<TagTeam> */
class Main extends BaseTable
{
    use ExecutesBusinessActions;

    #[\Override]
    protected bool $showActionColumn = true;

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
            ->withEmploymentStatusState()
            ->withAvailabilityState()
            ->withFirstEmployment()
            ->with('currentWrestlers')
            ->oldest('name');
    }

    protected function configure(): void
    {
        Gate::authorize('viewAny', TagTeam::class);
    }

    #[\Override]
    public function render(): View
    {
        return view('livewire.tag-teams.tables.main', [
            'rows' => $this->getRows(),
            'perPageOptions' => $this->perPageAccepted,
        ]);
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

    protected function getDefaultActionColumn(): Column
    {
        return Column::make(__('core.actions'))
            ->label(fn (TagTeam $row) => view('components.tables.columns.tag-team-actions', [
                'tagTeam' => $row,
            ])->render())
            ->html()
            ->excludeFromColumnSelect();
    }

    /**
     * @return array<int, Filter>
     */
    #[\Override]
    public function filters(): array
    {
        return [
            SelectFilter::make(__('core.status'))
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
}
