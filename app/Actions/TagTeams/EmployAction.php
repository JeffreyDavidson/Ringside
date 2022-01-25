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
     *
     * @return void
     */
    public function handle(TagTeam $tagTeam): void
    {
        $employmentDate = now();

        $tagTeam->currentWrestlers->each(function ($wrestler) use ($employmentDate) {
            $this->wrestlerRepository->employ($wrestler, $employmentDate);
            $wrestler->save();
        });

        $this->tagTeamRepository->employ($tagTeam, $employmentDate);
        $tagTeam->save();
    }
}
