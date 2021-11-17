<?php

namespace App\Actions\TagTeams;

use App\Models\TagTeam;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Unretire a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @return void
     */
    public function handle(TagTeam $tagTeam): void
    {
        $unretiredDate = now()->toDateTimeString();

        $this->tagTeamRepository->unretire($tagTeam, $unretiredDate);

        foreach ($tagTeam->currentWrestlers as $wrestler) {
            $this->wrestlerRepository->unretire($wrestler, $unretiredDate);
            $this->wrestlerRepository->employ($wrestler, $unretiredDate);
            $wrestler->updateStatus()->save();
        }

        $this->tagTeamRepository->employ($tagTeam, $unretiredDate);
        $tagTeam->updateStatus()->save();
    }
}
