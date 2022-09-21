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
     * @param  \App\Models\Manager  $manager
     * @param  \Illuminate\Support\Carbon|null  $releaseDate
     * @return void
     *
     * @throws \App\Exceptions\CannotBeReleasedException
     */
    public function handle(Manager $manager, ?Carbon $releaseDate = null): void
    {
        throw_if($manager->canBeReleased(), CannotBeReleasedException::class);

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
