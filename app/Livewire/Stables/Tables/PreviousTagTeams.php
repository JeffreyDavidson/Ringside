<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Builders\Roster\StableMembershipBuilder;
use App\Livewire\Base\Tables\BasePreviousMembersTable;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableTagTeam;
use Livewire\Attributes\Locked;

/** @extends BasePreviousMembersTable<StableTagTeam> */
class PreviousTagTeams extends BasePreviousMembersTable
{
    #[\Override]
    protected string $resourceName = 'tag teams';

    #[\Override]
    protected string $databaseTableName = 'stables_tag_teams';

    #[\Override]
    protected string $memberRelation = 'tagTeam';

    #[\Override]
    protected string $memberLabelGroup = 'tag-teams';

    #[\Override]
    protected string $dateLabelGroup = 'stables';

    #[Locked]
    public ?int $stableId = null;

    /** @return StableMembershipBuilder<StableTagTeam> */
    public function builder(): StableMembershipBuilder
    {
        $stableId = $this->requireContextId($this->stableId ?? null, 'stable');

        return StableTagTeam::query()
            ->with('tagTeam')
            ->forStableId($stableId)
            ->forHistory();
    }

    protected function configure(): void
    {
        $this->authorizeContextRecord(Stable::class, $this->stableId, 'stable');

        $this->addAdditionalSelects([
            'stables_tag_teams.tag_team_id',
            'stables_tag_teams.stable_id',
            'stables_tag_teams.joined_at',
            'stables_tag_teams.left_at',
        ]);
    }
}
