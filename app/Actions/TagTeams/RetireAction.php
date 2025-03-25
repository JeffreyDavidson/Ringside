<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\RetireAction as WrestlersRetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Retire a tag team.
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    public function handle(TagTeam $tagTeam, ?Carbon $retirementDate = null): void
    {
        $this->ensureCanBeRetired($tagTeam);

        $retirementDate ??= now();

        if ($tagTeam->isSuspended()) {
            ReinstateAction::run($tagTeam, $retirementDate);
        }

        $tagTeam->currentWrestlers
            ->each(fn (Wrestler $wrestler) => WrestlersRetireAction::run($wrestler, $retirementDate));

        if ($tagTeam->isCurrentlyEmployed()) {
            $this->tagTeamRepository->release($tagTeam, $retirementDate);
        }

        $this->tagTeamRepository->retire($tagTeam, $retirementDate);
    }

    /**
     * Ensure a tag team can be retired.
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    private function ensureCanBeRetired(TagTeam $tagTeam): void
    {
        if ($tagTeam->isUnemployed()) {
            throw CannotBeRetiredException::unemployed();
        }

        if ($tagTeam->hasFutureEmployment()) {
            throw CannotBeRetiredException::hasFutureEmployment();
        }

        if ($tagTeam->isRetired()) {
            throw CannotBeRetiredException::retired();
        }
    }
}
