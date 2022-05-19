<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\ReinstateAction as WrestlersReinstateAction;
use App\Models\TagTeam;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Reinstate a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \Illuminate\Support\Carbon|null  $reinstatementDate
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
