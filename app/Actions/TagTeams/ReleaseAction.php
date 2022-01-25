<?php

namespace App\Actions\TagTeams;

use App\Models\TagTeam;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Release a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     *
     * @return void
     */
    public function handle(TagTeam $tagTeam): void
    {
        $releaseDate = now();

        if ($tagTeam->isSuspended()) {
            $this->tagTeamRepository->reinstate($tagTeam, $releaseDate);

            $tagTeam->currentWrestlers->each(function ($wrestler) use ($releaseDate) {
                $this->wrestlerRepository->reinstate($wrestler, $releaseDate);
            });
        }

        $this->tagTeamRepository->release($tagTeam, $releaseDate);
        $tagTeam->save();

        $tagTeam->currentWrestlers->each(function ($wrestler) use ($releaseDate) {
            $this->wrestlerRepository->release($wrestler, $releaseDate);
            $wrestler->save();
        });
    }
}
