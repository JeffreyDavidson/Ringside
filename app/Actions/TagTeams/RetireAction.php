<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\RetireAction as WrestlersRetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Models\TagTeam;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Retire a tag team.
     *
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    public function handle(TagTeam $tagTeam, ?Carbon $retirementDate = null): void
    {
        throw_if($tagTeam->isUnemployed(), CannotBeRetiredException::class, $tagTeam.' is unemployed and cannot be retired.');
        throw_if($tagTeam->isReleased(), CannotBeRetiredException::class, $tagTeam.' is released and cannot be retired.');
        throw_if($tagTeam->hasFutureEmployment(), CannotBeRetiredException::class, $tagTeam.' is currently not  and cannot be retired.');
        throw_if($tagTeam->isRetired(), CannotBeRetiredException::class, $tagTeam.' is already retired and cannot be retired again.');

        $retirementDate ??= now();

        if ($tagTeam->isSuspended()) {
            ReinstateAction::run($tagTeam, $retirementDate);
        }

        $tagTeam->currentWrestlers->each(fn ($wrestler) => WrestlersRetireAction::run($wrestler, $retirementDate));

        $this->tagTeamRepository->release($tagTeam, $retirementDate);
        $this->tagTeamRepository->retire($tagTeam, $retirementDate);
    }
}
