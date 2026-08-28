<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Lifecycle\ChampionshipReignManager;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\Relationships\ManagerAssignmentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EndCurrentRelationshipsAction
{
    public function __construct(
        private readonly ChampionshipReignManager $championshipReigns,
        private readonly ManagerAssignmentService $managerAssignments,
    ) {}

    public function handle(Wrestler $wrestler, Carbon $effectiveDate): void
    {
        DB::transaction(function () use ($wrestler, $effectiveDate): void {
            $lockedWrestler = $wrestler->refreshForUpdate();

            TagTeamWrestler::query()
                ->forWrestlerId($lockedWrestler->id)
                ->current()
                ->update(['left_at' => $effectiveDate]);

            StableWrestler::query()
                ->whereBelongsTo($lockedWrestler)
                ->current()
                ->update(['left_at' => $effectiveDate]);

            $this->managerAssignments->endAssignmentsFor($lockedWrestler, $effectiveDate);

            $this->championshipReigns->endCurrentReignsForChampion($lockedWrestler, $effectiveDate);
        });
    }
}
