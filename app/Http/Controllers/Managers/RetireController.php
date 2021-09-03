<?php

namespace App\Http\Controllers\Managers;

use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\RetireRequest;
use App\Models\Manager;
use App\Repositories\ManagerRepository;

class RetireController extends Controller
{
    /**
     * Retire a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\RetireRequest  $request
     * @param  \App\Repositories\ManagerRepository $managerRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, RetireRequest $request, ManagerRepository $managerRepository)
    {
        throw_unless($manager->canBeRetired(), new CannotBeRetiredException);

        $retirementDate = now()->toDateTimeString();

        if ($manager->isSuspended()) {
            $managerRepository->reinstate($manager, $retirementDate);
        }

        if ($manager->isInjured()) {
            $managerRepository->clearInjury($manager, $retirementDate);
        }

        $managerRepository->release($manager, $retirementDate);
        $managerRepository->retire($manager, $retirementDate);
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
