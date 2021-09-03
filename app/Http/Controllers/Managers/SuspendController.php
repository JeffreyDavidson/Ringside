<?php

namespace App\Http\Controllers\Managers;

use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\SuspendRequest;
use App\Models\Manager;
use App\Repositories\ManagerRepository;

class SuspendController extends Controller
{
    /**
     * Suspend a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\SuspendRequest  $request
     * @param  \App\Repositories\ManagerRepository $managerRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, SuspendRequest $request, ManagerRepository $managerRepository)
    {
        throw_unless($manager->canBeSuspended(), new CannotBeSuspendedException);

        $suspensionDate = now()->toDateTimeString();

        $managerRepository->suspend($manager, $suspensionDate);
        $manager->updateStatus()->save();

        return redirect()->route('managers.index');
    }
}
