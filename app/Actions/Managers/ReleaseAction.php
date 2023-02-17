<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\CannotBeReleasedException;
use App\Models\Manager;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Release a manager.
     *
     * @throws \App\Exceptions\CannotBeReleasedException
     */
    public function handle(Manager $manager, ?Carbon $releaseDate = null): void
    {
        throw_if($manager->isUnemployed(), CannotBeReleasedException::class, $manager.' is unemployed and cannot be released.');
        throw_if($manager->isReleased(), CannotBeReleasedException::class, $manager.' is already released.');
        throw_if($manager->hasFutureEmployment(), CannotBeReleasedException::class, $manager.' has not been officially employed and cannot be released.');
        throw_if($manager->isRetired(), CannotBeReleasedException::class, $manager.' has is retired and cannot be released.');

        $releaseDate ??= now();

        if ($manager->isSuspended()) {
            ReinstateAction::run($manager, $releaseDate);
        }

        if ($manager->isInjured()) {
            ClearInjuryAction::run($manager, $releaseDate);
        }

        $this->managerRepository->release($manager, $releaseDate);

        $manager->currentTagTeams
            ->whenNotEmpty(fn () => RemoveFromCurrentTagTeamsAction::run($manager));

        $manager->currentWrestlers
            ->whenNotEmpty(fn () => RemoveFromCurrentWrestlersAction::run($manager));
    }
}
