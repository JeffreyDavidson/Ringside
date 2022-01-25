<?php

namespace App\Actions\TagTeams;

use App\Models\TagTeam;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Retire a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     *
     * @return void
     */
    public function handle(TagTeam $tagTeam): void
    {
        $retirementDate = now();

        if ($tagTeam->isSuspended()) {
            $this->tagTeamRepository->reinstate($tagTeam, $retirementDate);

            $tagTeam->currentWrestlers->each(function ($wrestler) use ($retirementDate) {
                $this->wrestlerRepository->reinstate($wrestler, $retirementDate);
            });
        }

        $tagTeam->currentWrestlers->each(function ($wrestler) use ($retirementDate) {
            $this->wrestlerRepository->release($wrestler, $retirementDate);
            $this->wrestlerRepository->retire($wrestler, $retirementDate);
            $wrestler->save();
        });

        $this->tagTeamRepository->release($tagTeam, $retirementDate);
        $this->tagTeamRepository->retire($tagTeam, $retirementDate);
        $tagTeam->save();
    }
}
