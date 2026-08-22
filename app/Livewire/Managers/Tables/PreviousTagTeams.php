<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Builders\Roster\ManagerAssignmentBuilder;
use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Roster\TagTeams\TagTeamManager;
use Livewire\Attributes\Locked;
use LogicException;

/** @extends DataTableComponent<TagTeamManager> */
class PreviousTagTeams extends DataTableComponent
{
    use ShowTableTrait;

    /**
     * ManagerId to use for component.
     */
    #[Locked]
    public ?int $managerId;

    protected string $databaseTableName = 'tag_teams_managers';

    protected string $resourceName = 'tag teams';

    /** @return ManagerAssignmentBuilder<TagTeamManager> */
    public function builder(): ManagerAssignmentBuilder
    {
        if (! isset($this->managerId)) {
            throw new LogicException('A manager was not provided.');
        }

        return TagTeamManager::query()
            ->forManagerId($this->managerId)
            ->ended()
            ->with('tagTeam')
            ->mostRecentlyHiredFirst();
    }

    protected function configure(): void
    {
        $this->addAdditionalSelects([
            'tag_teams_managers.tag_team_id as tag_team_id',
        ]);
    }

    /**
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
