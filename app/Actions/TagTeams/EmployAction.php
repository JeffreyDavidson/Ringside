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
     * @param  \Carbon\Carbon|null  $startDate
     *
     * @return void
     */
    public function handle(TagTeam $tagTeam, ?Carbon $startDate = null): void
    {
        $startDate ??= now();

        $tagTeam->currentWrestlers->each(function ($wrestler) use ($startDate) {
            $this->wrestlerRepository->employ($wrestler, $startDate);
            $wrestler->save();
        });

        $this->tagTeamRepository->employ($tagTeam, $startDate);
        $tagTeam->save();
    }
}
