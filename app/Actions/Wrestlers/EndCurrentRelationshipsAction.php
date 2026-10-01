<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Managers\EndManagerAssignmentsAction;
use App\Lifecycle\Periods\OpenPeriodEnder;
use App\Lifecycle\Titles\ChampionshipReignManager;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EndCurrentRelationshipsAction
{
    public function __construct(
        private readonly ChampionshipReignManager $championshipReigns,
        private readonly EndManagerAssignmentsAction $endManagerAssignmentsAction,
    ) {}

    public function handle(Wrestler $wrestler, Carbon $effectiveDate): void
    {
        DB::transaction(function () use ($wrestler, $effectiveDate): void {
            $lockedWrestler = $wrestler->refreshForUpdate();

            OpenPeriodEnder::end(
                TagTeamWrestler::query()->forWrestlerId($lockedWrestler->id),
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
