<?php

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\ReinstateAction as WrestlersReinstateAction;
use App\Models\TagTeam;
use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Reinstate a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \Carbon\Carbon|null  $reinstatementDate
     *
     * @return void
     */
    public function handle(TagTeam $tagTeam, ?Carbon $reinstatementDate = null): void
    {
        $reinstatementDate ??= now();

        $tagTeam->currentWrestlers->each(function ($wrestler) use ($reinstatementDate) {
            WrestlersReinstateAction::run($wrestler, $reinstatementDate);
        });

        $this->tagTeamRepository->reinstate($tagTeam, $reinstatementDate);
        $tagTeam->save();
    }
}
