<?php

namespace App\Http\Controllers\Managers;

use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\ReinstateRequest;
use App\Models\Manager;
use App\Repositories\ManagerRepository;

class ReinstateController extends Controller
{
    /**
     * Reinstate a suspended manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\ReinstateRequest  $request
     * @param  \App\Repositories\ManagerRepository  $managerRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, ReinstateRequest $request, ManagerRepository $managerRepository)
    {
        throw_unless($manager->canBeReinstated(), new CannotBeReinstatedException);

        $reinstatementDate = now()->toDateTimeString();

        $managerRepository->reinstate($manager, $reinstatementDate);
        $manager->updateStatus()->save();

        return redirect()->route('managers.index');
    }
}
