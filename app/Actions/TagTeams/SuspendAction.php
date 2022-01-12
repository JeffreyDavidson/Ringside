<?php

namespace App\Actions\TagTeams;

use App\Models\TagTeam;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Suspend a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     *
     * @return void
     */
    public function handle(TagTeam $tagTeam): void
    {
        $suspensionDate = now();

        $tagTeam->currentWrestlers->each(function ($wrestler) use ($suspensionDate) {
            $this->wrestlerRepository->suspend($wrestler, $suspensionDate);
            $wrestler->save();
        });

        $this->tagTeamRepository->suspend($tagTeam, $suspensionDate);
        $tagTeam->save();
    }
}
