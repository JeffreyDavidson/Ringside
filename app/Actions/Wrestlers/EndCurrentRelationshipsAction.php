<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Managers\EndManagerAssignmentsAction;
use App\Builders\Roster\TagTeamMembershipBuilder;
use App\Lifecycle\Periods\OpenPeriodEnder;
use App\Lifecycle\Titles\ChampionshipReignManager;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EndCurrentRelationshipsAction
{
    public function __construct(
        private readonly ChampionshipReignManager $championshipReigns,
        private readonly EndManagerAssignmentsAction $endManagerAssignmentsAction,
        private readonly LockCurrentTagTeamsAction $lockCurrentTagTeams,
    ) {}

    /**
     * Ends the wrestler's current memberships, manager assignments and reigns, and the reigns of every tag team they leave.
     *
     * @param  TagTeam|null  $retainedTagTeam  A tag team whose membership stays open, such as the team retiring with this wrestler.
     */
    public function handle(Wrestler $wrestler, Carbon $effectiveDate, ?TagTeam $retainedTagTeam = null): void
    {
        DB::transaction(function () use ($wrestler, $effectiveDate, $retainedTagTeam): void {
            $lockedWrestler = $wrestler->refreshForUpdate();

            // A team that loses a member vacates its titles on the day the member leaves.
            $this->lockCurrentTagTeams->handle($lockedWrestler, $retainedTagTeam)
                ->each(fn (TagTeam $tagTeam) => $this->championshipReigns->endCurrentReignsForChampion($tagTeam, $effectiveDate));

            OpenPeriodEnder::end(
                TagTeamWrestler::query()
                    ->forWrestlerId($lockedWrestler->id)
                    ->when($retainedTagTeam, fn (TagTeamMembershipBuilder $query, TagTeam $tagTeam): TagTeamMembershipBuilder => $query->excludingTagTeamId($tagTeam->id)),
                'joined_at',
                'left_at',
                $effectiveDate,
            );

            OpenPeriodEnder::end(
                StableWrestler::query()->whereBelongsTo($lockedWrestler),
                'joined_at',
                'left_at',
                $effectiveDate,
            );

            $this->endManagerAssignmentsAction->handle($lockedWrestler, $effectiveDate);

            $this->championshipReigns->endCurrentReignsForChampion($lockedWrestler, $effectiveDate);
        });
    }
}
