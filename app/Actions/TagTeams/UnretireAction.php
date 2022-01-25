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
     *
     * @return void
     */
    public function handle(TagTeam $tagTeam): void
    {
        $unretiredDate = now();

        $this->tagTeamRepository->unretire($tagTeam, $unretiredDate);

        $tagTeam->currentWrestlers->each(function ($wrestler) use ($unretiredDate) {
            $this->wrestlerRepository->unretire($wrestler, $unretiredDate);
            $this->wrestlerRepository->employ($wrestler, $unretiredDate);
            $wrestler->save();
        });

        $this->tagTeamRepository->employ($tagTeam, $unretiredDate);
        $tagTeam->save();
    }
}
