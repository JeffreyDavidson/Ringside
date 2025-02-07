<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Builders\TagTeamBuilder;
use App\Enums\EmploymentStatus;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Concerns\Columns\FirstEmploymentDateColumn;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\TagTeam;
use App\View\Filters\FirstEmploymentFilter;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class TagTeamsTable extends BaseTableWithActions
{
    use HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'tag_teams';

    protected string $routeBasePath = 'tag-teams';

    protected string $resourceName = 'tag teams';

    /**
     * @return TagTeamBuilder<TagTeam>
     */
    public function builder(): TagTeamBuilder
    {
        return TagTeam::query()
            ->with('currentEmployment')
            ->oldest('name')
            ->when($this->getAppliedFilterWithValue('Status'), fn ($query, $status) => $query->where('status', $status));
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
}
