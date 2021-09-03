<?php

namespace App\Http\Controllers\Managers;

use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\ReleaseRequest;
use App\Models\Manager;
use App\Repositories\ManagerRepository;

class ReleaseController extends Controller
{
    /**
     * Release a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\ReleaseRequest  $request
     * @param  \App\Repositories\ManagerRepository  $managerRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, ReleaseRequest $request, ManagerRepository $managerRepository)
    {
        throw_unless($manager->canBeReleased(), new CannotBeReleasedException);

        $releaseDate = now()->toDateTimeString();

        if ($manager->isSuspended()) {
            $managerRepository->reinstate($manager, $releaseDate);
        }

        if ($manager->isInjured()) {
            $managerRepository->clearInjury($manager, $releaseDate);
        }

        $managerRepository->release($manager, $releaseDate);
        $manager->updateStatus()->save();

        if ($manager->has('currentTagTeams')) {
            $managerRepository->removeFromCurrentTagTeams($manager);
        }

        if ($manager->has('currentWrestlers')) {
            $managerRepository->removeFromCurrentWrestlers($manager);
        }

        return redirect()->route('managers.index');
    }
}
