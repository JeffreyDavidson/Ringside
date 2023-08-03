<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\SuspendAction as WrestlerSuspendAction;
use App\Exceptions\CannotBeSuspendedException;
use App\Models\TagTeam;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Suspend a tag team.
     *
     * @throws \App\Exceptions\CannotBeSuspendedException
     */
    public function handle(TagTeam $tagTeam, Carbon $suspensionDate = null): void
    {
        $this->ensureCanBeSuspended($tagTeam);

        $suspensionDate ??= now();

        $tagTeam->currentWrestlers->each(fn ($wrestler) => WrestlerSuspendAction::run($wrestler, $suspensionDate));

        $this->tagTeamRepository->suspend($tagTeam, $suspensionDate);
    }

    /**
     * Ensure tag team can be suspended.
     *
     * @throws \App\Exceptions\CannotBeSuspendedException
     */
    private function ensureCanBeSuspended(TagTeam $tagTeam): void
    {
        if ($tagTeam->isUnemployed()) {
            throw CannotBeSuspendedException::unemployed($tagTeam);
        }

        if ($tagTeam->hasFutureEmployment()) {
            throw CannotBeSuspendedException::hasFutureEmployment($tagTeam);
        }

        if ($tagTeam->isSuspended()) {
            throw CannotBeSuspendedException::suspended($tagTeam);
        }
    }
}
