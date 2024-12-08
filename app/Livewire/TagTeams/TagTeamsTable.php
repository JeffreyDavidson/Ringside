<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams;

use App\Builders\TagTeamBuilder;
use App\Enums\TagTeamStatus;
use App\Livewire\Concerns\BaseTableTrait;
use App\Livewire\Concerns\Columns\HasFirstEmploymentDateColumn;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasFirstEmploymentDateFilter;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\TagTeam;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class TagTeamsTable extends DataTableComponent
{
    use BaseTableTrait, HasFirstEmploymentDateColumn, HasFirstEmploymentDateFilter, HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'tag_teams';

    protected string $routeBasePath = 'tag-teams';

    protected string $resourceName = 'tag teams';

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
            $this->getDefaultFirstEmploymentDateColumn(),
        ];
    }

    /**
     * Undocumented function
     *
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        $statuses = collect(TagTeamStatus::cases())->pluck('name', 'value')->toArray();

        return [
            $this->getDefaultStatusFilter($statuses),
            $this->getDefaultFirstEmploymentDateFilter(),
        ];
    }
}
