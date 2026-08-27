<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Lifecycle\ChampionshipReignManager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Services\Roster\TagTeams\TagTeamMembershipService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EndCurrentRelationshipsAction
{
    public function __construct(
        private readonly ChampionshipReignManager $championshipReigns,
        private readonly TagTeamMembershipService $memberships,
    ) {}

    public function handle(TagTeam $tagTeam, Carbon $effectiveDate): void
    {
        DB::transaction(function () use ($tagTeam, $effectiveDate): void {
            $lockedTagTeam = TagTeam::query()
                ->whereKey($tagTeam->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->memberships->endCurrentMemberships($lockedTagTeam, $effectiveDate);

            $this->championshipReigns->endCurrentReignsForChampion($lockedTagTeam, $effectiveDate);
        });
    }
}
