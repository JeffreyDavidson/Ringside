<?php

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\EmployAction as WrestlersEmployAction;
use App\Models\TagTeam;
use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Employ a tagTeam.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \Carbon\Carbon|null  $startDate
     * @return void
     */
    public function handle(TagTeam $tagTeam, ?Carbon $startDate = null): void
    {
        $startDate ??= now();

        $tagTeam->currentWrestlers->each(function ($wrestler) use ($startDate) {
            WrestlersEmployAction::run($wrestler, $startDate);
        });

        $this->tagTeamRepository->employ($tagTeam, $startDate);
        $tagTeam->save();
    }
}
