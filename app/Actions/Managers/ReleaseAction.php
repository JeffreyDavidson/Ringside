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
     */
    public function handle(Manager $manager, ?Carbon $releaseDate = null): void
    {
        $this->ensureCanBeReleased($manager);

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

    /**
     * Ensure a manager can be released.
     *
     * @throws \App\Exceptions\CannotBeReleasedException
     */
    private function ensureCanBeReleased(Manager $manager): void
    {
        if ($manager->isUnemployed()) {
            throw CannotBeReleasedException::unemployed($manager);
        }

        if ($manager->isReleased()) {
            throw CannotBeReleasedException::released($manager);
        }

        if ($manager->hasFutureEmployment()) {
            throw CannotBeReleasedException::hasFutureEmployment($manager);
        }

        if ($manager->isRetired()) {
            throw CannotBeReleasedException::retired($manager);
        }
    }
}
