<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Builders\Roster\TagTeamMembershipBuilder;
use App\Livewire\Base\Tables\BasePreviousMembersTable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use Livewire\Attributes\Locked;

/** @extends BasePreviousMembersTable<TagTeamWrestler> */
class PreviousWrestlers extends BasePreviousMembersTable
{
    #[\Override]
    protected string $resourceName = 'wrestlers';

    #[\Override]
    protected string $databaseTableName = 'tag_teams_wrestlers';

    #[\Override]
    protected string $memberRelation = 'wrestler';

    #[\Override]
    protected string $memberLabelGroup = 'wrestlers';

    #[\Override]
    protected string $dateLabelGroup = 'tag-teams';

    #[Locked]
    public ?int $tagTeamId = null;

    /** @return TagTeamMembershipBuilder<TagTeamWrestler> */
    public function builder(): TagTeamMembershipBuilder
    {
        $tagTeamId = $this->requireContextId($this->tagTeamId ?? null, 'tag team');

        return TagTeamWrestler::query()
            ->with('wrestler')
            ->forTagTeamId($tagTeamId)
            ->forHistory();
    }

    protected function configure(): void
    {
        $this->authorizeContextRecord(TagTeam::class, $this->tagTeamId, 'tag team');

        $this->addAdditionalSelects([
            'tag_teams_wrestlers.wrestler_id',
            'tag_teams_wrestlers.tag_team_id',
            'tag_teams_wrestlers.joined_at',
            'tag_teams_wrestlers.left_at',
        ]);
    }
}
