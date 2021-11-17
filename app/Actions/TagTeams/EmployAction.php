<?php

namespace App\Actions\TagTeams;

use App\Models\TagTeam;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Unretire a tagTeam.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @return void
     */
    public function handle(TagTeam $tagTeam): void
    {
        $employmentDate = now()->toDateTimeString();

        $this->tagTeamRepository->employ($tagTeam, $employmentDate);

        foreach ($tagTeam->currentWrestlers as $wrestler) {
            $this->wrestlerRepository->employ($wrestler, $employmentDate);
            $wrestler->updateStatus()->save();
        }

        $tagTeam->updateStatus()->save();
    }
}
