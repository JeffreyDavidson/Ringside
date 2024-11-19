<?php

declare(strict_types=1);

namespace App\Livewire\Managers;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\TagTeamManager;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;

class PreviousTagTeamsTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'tag_teams_managers';

    protected string $resourceName = 'tag teams';

    /**
     * ManagerId to use for component.
     */
    public ?int $managerId;

    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new \Exception("You didn't specify a manager");
        }

        return TagTeamManager::query()
            ->where('manager_id', $this->managerId)
            ->whereNotNull('left_at')
            ->orderByDesc('hired_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'tag_teams_managers.tag_team_id as tag_team_id',
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make(__('tag-teams.name'), 'tagTeam.name'),
            DateColumn::make(__('managers.date_hired'), 'hired_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('managers.date_fired'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }
}
