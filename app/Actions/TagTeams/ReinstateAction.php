<?php

namespace App\Actions\TagTeams;

use App\Models\TagTeam;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Reinstate a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @return void
     */
    public function handle(TagTeam $tagTeam): void
    {
        $reinstatementDate = now()->toDateTimeString();

        foreach ($tagTeam->currentWrestlers as $wrestler) {
            $this->wrestlerRepository->reinstate($wrestler, $reinstatementDate);
            $this->wrestlerRepository->employ($wrestler, $reinstatementDate);
            $wrestler->save();
        }

        $this->tagTeamRepository->reinstate($tagTeam, $reinstatementDate);
        $tagTeam->save();
    }
}
