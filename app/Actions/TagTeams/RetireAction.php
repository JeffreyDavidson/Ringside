<?php

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\RetireAction as WrestlersRetireAction;
use App\Models\TagTeam;
use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Retire a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \Carbon\Carbon|null  $retirementDate
     * @return void
     */
    public function handle(TagTeam $tagTeam, ?Carbon $retirementDate = null): void
    {
        $retirementDate ??= now();

        if ($tagTeam->isSuspended()) {
            ReinstateAction::run($tagTeam, $retirementDate);
        }

        $tagTeam->currentWrestlers->each(function ($wrestler) use ($retirementDate) {
            WrestlersRetireAction::run($wrestler, $retirementDate);
        });

        $this->tagTeamRepository->release($tagTeam, $retirementDate);
        $this->tagTeamRepository->retire($tagTeam, $retirementDate);
        $tagTeam->save();
    }
}
