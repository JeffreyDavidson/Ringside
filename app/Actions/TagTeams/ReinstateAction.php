<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\ReinstateAction as WrestlersReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Reinstate a tag team.
     *
     * @throws \App\Exceptions\CannotBeReinstatedException
     */
    public function handle(TagTeam $tagTeam, ?Carbon $reinstatementDate = null): void
    {
        $this->ensureCanBeReinstated($tagTeam);

        $reinstatementDate ??= now();

        $tagTeam->currentWrestlers
            ->each(fn (Wrestler $wrestler) => WrestlersReinstateAction::run($wrestler, $reinstatementDate));

        $this->tagTeamRepository->reinstate($tagTeam, $reinstatementDate);
    }

    /**
     * Ensure a tag team can be reinstated.
     *
     * @throws \App\Exceptions\CannotBeReinstatedException
     */
    private function ensureCanBeReinstated(TagTeam $tagTeam): void
    {
        if ($tagTeam->isUnemployed()) {
            throw CannotBeReinstatedException::unemployed($tagTeam);
        }

        if ($tagTeam->isReleased()) {
            throw CannotBeReinstatedException::released($tagTeam);
        }

        if ($tagTeam->hasFutureEmployment()) {
            throw CannotBeReinstatedException::hasFutureEmployment($tagTeam);
        }

        if ($tagTeam->isRetired()) {
            throw CannotBeReinstatedException::retired($tagTeam);
        }

        if ($tagTeam->isBookable()) {
            throw CannotBeReinstatedException::bookable($tagTeam);
        }
    }
}
