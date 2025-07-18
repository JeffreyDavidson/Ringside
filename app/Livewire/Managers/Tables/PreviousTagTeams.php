<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\TagTeams\TagTeamManager;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;

class PreviousTagTeams extends DataTableComponent
{
    use ShowTableTrait;

    /**
     * ManagerId to use for component.
     */
    public ?int $managerId;

    protected string $databaseTableName = 'tag_teams_managers';

    protected string $resourceName = 'tag teams';

    /**
     * @return Builder<TagTeamManager>
     */
    public function builder(): Builder
    {
        if (! isset($this->managerId)) {
            throw new Exception("You didn't specify a manager");
        }

        return TagTeamManager::query()
            ->where('manager_id', $this->managerId)
            ->whereNotNull('fired_at')
            ->orderByDesc('hired_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'tag_teams_managers.tag_team_id as tag_team_id',
        ]);
    }

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('tag-teams.name'), 'tagTeam.name'),
            DateColumn::make(__('managers.date_hired'), 'hired_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('managers.date_fired'), 'fired_at')
                ->outputFormat('Y-m-d'),
        ];
    }
}
