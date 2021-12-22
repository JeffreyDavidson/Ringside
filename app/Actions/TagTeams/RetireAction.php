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
     * @return void
     */
    public function handle(TagTeam $tagTeam): void
    {
        $retirementDate = now()->toDateTimeString();

        if ($tagTeam->isSuspended()) {
            $this->tagTeamRepository->reinstate($tagTeam, $retirementDate);

            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $this->wrestlerRepository->reinstate($wrestler, $retirementDate);
            }
        }

        foreach ($tagTeam->currentWrestlers as $wrestler) {
            $this->wrestlerRepository->release($wrestler, $retirementDate);
            $this->wrestlerRepository->retire($wrestler, $retirementDate);
            $wrestler->save();
        }

        $this->tagTeamRepository->release($tagTeam, $retirementDate);
        $this->tagTeamRepository->retire($tagTeam, $retirementDate);
        $tagTeam->save();
    }
}
