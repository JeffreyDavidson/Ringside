<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Lifecycle\Periods\OpenPeriodEnder;
use App\Lifecycle\Titles\ChampionshipReignManager;
use App\Models\Roster\Stables\StableTagTeam;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EndCurrentRelationshipsAction
{
    public function __construct(
        private readonly ChampionshipReignManager $championshipReigns,
        private readonly EndMembershipsAction $endMembershipsAction,
    ) {}

    public function handle(TagTeam $tagTeam, Carbon $effectiveDate): void
    {
        DB::transaction(function () use ($tagTeam, $effectiveDate): void {
            $lockedTagTeam = $tagTeam->refreshForUpdate();

            $this->endMembershipsAction->handle($lockedTagTeam, $effectiveDate);

            OpenPeriodEnder::end(StableTagTeam::query()->whereBelongsTo($lockedTagTeam), 'joined_at', 'left_at', $effectiveDate);

            $this->championshipReigns->endCurrentReignsForChampion($lockedTagTeam, $effectiveDate);
        });
    }
}
