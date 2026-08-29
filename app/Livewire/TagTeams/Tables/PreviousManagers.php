<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Builders\Roster\ManagerAssignmentBuilder;
use App\Livewire\Base\Tables\BasePreviousManagersTable;
use App\Models\Roster\TagTeams\TagTeamManager;
use Livewire\Attributes\Locked;

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
        $tagTeamId = $this->requireContextId($this->tagTeamId ?? null, 'tag team');

        return TagTeamManager::query()
            ->forTagTeamId($tagTeamId)
            ->forHistory()
            ->with('manager');
    }

    protected function configure(): void
    {
        $this->addAdditionalSelects([
            'tag_teams_managers.manager_id',
        ]);
    }
}
