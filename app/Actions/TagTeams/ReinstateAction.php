<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\ReinstateAction as WrestlersReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

final class ReinstateAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Reinstate a tag team.
     *
     * @throws CannotBeReinstatedException
     */
    public function handle(TagTeam $tagTeam, ?Carbon $reinstatementDate = null): void
    {
        $this->ensureCanBeReinstated($tagTeam);

        $reinstatementDate ??= now();

        $tagTeam->currentWrestlers
            ->each(fn (Wrestler $wrestler) => resolve(WrestlersReinstateAction::class)->handle($wrestler, $reinstatementDate));

        $this->tagTeamRepository->reinstate($tagTeam, $reinstatementDate);
    }

    /**
     * Ensure a tag team can be reinstated.
     *
     * @throws CannotBeReinstatedException
     */
    private function ensureCanBeReinstated(TagTeam $tagTeam): void
    {
        if ($tagTeam->isUnemployed()) {
            throw CannotBeReinstatedException::unemployed();
        }

        if ($tagTeam->isReleased()) {
            throw CannotBeReinstatedException::released();
        }

        if ($tagTeam->hasFutureEmployment()) {
            throw CannotBeReinstatedException::hasFutureEmployment();
        }

        if ($tagTeam->isRetired()) {
            throw CannotBeReinstatedException::retired();
        }

        if ($tagTeam->isBookable()) {
            throw CannotBeReinstatedException::bookable();
        }
    }
}
