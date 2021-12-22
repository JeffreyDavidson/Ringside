<?php

namespace App\Actions\TagTeams;

use App\Models\TagTeam;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Employ a tagTeam.
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
            $wrestler->save();
        }

        $tagTeam->save();
    }
}
