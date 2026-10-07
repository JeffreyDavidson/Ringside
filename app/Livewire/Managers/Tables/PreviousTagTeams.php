<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Builders\Roster\ManagerAssignmentBuilder;
use App\Livewire\Base\Tables\BasePreviousManagedTable;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeamManager;
use Livewire\Attributes\Locked;

/** @extends BasePreviousManagedTable<TagTeamManager> */
class PreviousTagTeams extends BasePreviousManagedTable
{
    /**
     * ManagerId to use for component.
     */
    #[Locked]
    public ?int $managerId = null;

    #[\Override]
    protected string $databaseTableName = 'tag_teams_managers';

    #[\Override]
    protected string $resourceName = 'tag teams';

    #[\Override]
    protected string $managedRelation = 'tagTeam';

    #[\Override]
    protected string $managedLabelGroup = 'tag-teams';

    #[\Override]
    protected string $hiredLabelKey = 'managers.date_hired';

    #[\Override]
    protected string $firedLabelKey = 'managers.date_fired';

    /** @return ManagerAssignmentBuilder<TagTeamManager> */
    public function builder(): ManagerAssignmentBuilder
    {
        $managerId = $this->requireContextId($this->managerId ?? null, 'manager');

        return TagTeamManager::query()
            ->with('tagTeam')
            ->whereHas('tagTeam')
            ->forManagerId($managerId)
            ->forHistory();
    }

    protected function configure(): void
    {
        $this->authorizeContextRecord(Manager::class, $this->managerId, 'manager');

        $this->addAdditionalSelects([
            'tag_teams_managers.tag_team_id as tag_team_id',
        ]);
    }
}
