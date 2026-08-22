<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Builders\Roster\ManagerAssignmentBuilder;
use App\Livewire\Base\Tables\BasePreviousManagersTable;
use App\Models\Roster\TagTeams\TagTeamManager;
use Livewire\Attributes\Locked;
use LogicException;

/** @extends BasePreviousManagersTable<TagTeamManager> */
class PreviousManagers extends BasePreviousManagersTable
{
    protected string $databaseTableName = 'tag_teams_managers';

    /**
     * Tag Team to use for component.
     */
    #[Locked]
    public ?int $tagTeamId;

    /** @return ManagerAssignmentBuilder<TagTeamManager> */
    public function builder(): ManagerAssignmentBuilder
    {
        if (! isset($this->tagTeamId)) {
            throw new LogicException('A tag team was not provided.');
        }

        return TagTeamManager::query()
            ->where('tag_team_id', $this->tagTeamId)
            ->ended()
            ->with('manager')
            ->mostRecentlyHiredFirst();
    }

    protected function configure(): void
    {
        $this->addAdditionalSelects([
            'tag_teams_managers.manager_id',
        ]);
    }
}
